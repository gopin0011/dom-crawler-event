<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;

class EventController extends Controller
{
    static private $liputan6Source = 'LIPUTAN6DOTCOM';
    static private $urlLiputan6Index = 'https://www.liputan6.com/news/indeks';
    static private $dataType = ['Article', 'Photo', 'Video'];

    public function crawlerIdulAdha()
    {
        $url = 'https://publicholidays.co.id/id/idul-adha/';

        $data = $detail = [];

        $client = new Client(HttpClient::create(['timeout' => 2]));

        $crawler = $client->request('GET', $url);
        $crawler->filter('table[class="publicholidays phgtable "] > tbody > tr[class]')->each(function (Crawler $node) use(&$data, &$detail) {
            $node->children()->each(function(Crawler $field) use (&$detail) {
                array_push($detail, $field->text());

                $tanggal = null;

                if (preg_match("/\\s/", $field->text())) {
                    $str = explode(' ', $field->text());
                    if((int) $str[0]) {
                        switch($str[1]){
                            case 'Januari': $month = '1'; break;
                            case 'Februari': $month = '2'; break;
                            case 'Maret': $month = '3'; break;
                            case 'April': $month = '4'; break;
                            case 'Mei': $month = '5'; break;
                            case 'Juni': $month = '6'; break;
                            case 'Juli': $month = '7'; break;
                            case 'Agustus': $month = '8'; break;
                            case 'September': $month = '9'; break;
                            case 'Oktober': $month = '10'; break;
                            case 'November': $month = '11'; break;
                            case 'Desember': $month = '12'; break;
                        }
                        $tanggal = $str[0].'/'.$month.'/'.$detail[0];
                    }
                    if($tanggal) {
                        array_push($detail, $tanggal);
                    }
                }
            });
            array_push($data, $detail);
            $detail = [];
        });
        dump($data);
    }

    public function liputan6Index(Request $request)
    {
        $return = [
            'method' => $request->method(),
            'status' => false,
        ];

        $url = ($request->page) ? self::$urlLiputan6Index.'?page='.$request->page : self::$urlLiputan6Index.'?page=1';

        $client = new Client(HttpClient::create(['timeout' => 10]));

        try {
            $data = [];
            $totalPage = 1;

            $crawler = $client->request($request->method(), $url);

            $crawler->filter('article[class="main"]')->each(function (Crawler $node) use (&$data, &$totalPage) {
                $node->children()->filter('article[class="articles--rows--item"]')->each(function (Crawler $child) use (&$data, &$totalPage) {
                    $item = [];
                    $item['dataType'] = $child->attr('data-type');
                    $item['url'] = $child->children()->filter('h4 > a')->attr('href');
                    $item['title'] = $child->children()->filter('h4 > a')->attr('title');
                    $item['datetime'] = $child->children()->filter('time[class="articles--rows--item__time timeago"]')->attr('datetime');
                    $item['summary'] = $child->children()->filter('div[class="articles--rows--item__summary"]')->text();
                    $item['image'] = $child->children()->filter('img[class="articles--rows--item__figure-image-img"]')->attr('src');
                    $item['slug'] = $child->attr('data-slug');
                    array_push($data, $item);
                });
                $totalPage = $node->filter('li[class="simple-pagination__page-number"]:last-child')->attr('data-page');
            });
        }
        catch(\Exception $e) {
            $return['page'] = ($request->page) ?: 1;
            $return['reason'] = $e->getMessage();
            return $this->returnData($return, $data);
        }

        $return['status'] = true;
        $return['source'] = self::$liputan6Source;
        $return['page'] = ($request->page) ?: 1;
        $return['totalPage'] = $totalPage;

        return $this->returnData($return, $data);
    }

    public function getPost(Request $request)
    {
        $data = [];

        $return = [
            'method' => $request->method(),
            'status' => false,
        ];

        if (!in_array($request->source, [self::$liputan6Source]) || !in_array($request->dataType, self::$dataType) || !$request->filled('urlPath'))
        {
            $return['reason'] = 'Data request not correct';
            return $this->returnData($return, $data);
        }

        $return['source'] = $request->source;
        $return['url'] = $request->urlPath;

        $client = new Client(HttpClient::create(['timeout' => 10]));

        try {
            $crawler = $client->request($request->method(), $request->urlPath);

            if ($request->dataType == 'Article')
            {
                $crawler->filter('article[class="hentry main"]')->each(function (Crawler $node) use (&$all, &$data) {
                    $data['title'] = $node->children()->filter('div[class="read-page-upper"] > header[class="read-page--header"] > h1[class="read-page--header--title entry-title"]')->text();
                    $data['image'] = $node->children()->filter('div[class="read-page-upper"] > div[class="read-page--content"] > div[class="read-page--top-media"] > figure')->attr('data-image'); //a[class="read-page--photo-gallery--item__link"] > picture[class="read-page--photo-gallery--item__picture"] > img[class="read-page--photo-gallery--item__picture-lazyload lazyloaded"]')->attr('src');
                    $data['content'] = $node->children()->filter('div[class="read-page-upper"] > div[class="read-page--content"] > div[class="article-content-body article-content-body_with-aside"]')->text();
                });
            }
        }
        catch(\Exception $e) {
            $return['reason'] = $e->getMessage();
            return $this->returnData($return, $data);
        }

        return $this->returnData($return, $data);
    }

    public function returnData($return = [], $data =[])
    {
        $return['result'] = $data;

        return json_encode($return);
    }
}
