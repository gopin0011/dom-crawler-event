<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\DomCrawler\Crawler;
use Goutte\Client;
use Symfony\Component\HttpClient\HttpClient;

class EventController extends Controller
{
    public function crawlerIdulAdha()
    {
        $url = 'https://publicholidays.co.id/id/idul-adha/';
        // $url = 'http://localhost:8000/publicholiday';

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
        $status = true;
        $totalPage = 1;
        $page = ($request->page) ?: 1;

        // $url = 'https://www.liputan6.com/news/indeks?page=2';
        $url = 'https://www.liputan6.com/news/indeks';

        $all = [
            'totalPage' => $totalPage,
            'result' => []
        ];

        $data = [];

        $method = 'GET';

        $client = new Client(HttpClient::create(['timeout' => 10]));

        try {
            $crawler = $client->request($method, $url);

            $crawler->filter('article[class="main"]')->each(function (Crawler $node) use (&$all, &$data) {
                $node->children()->filter('article[class="articles--rows--item"]')->each(function (Crawler $item) use (&$all, &$data) {
                        $data['url'] = $item->children()->filter('h4 > a')->attr('href');
                        $data['title'] = $item->children()->filter('h4 > a')->attr('title');
                        $data['datetime'] = $item->children()->filter('time[class="articles--rows--item__time timeago"]')->attr('datetime');
                        $data['summary'] = $item->children()->filter('div[class="articles--rows--item__summary"]')->text();
                        $data['image'] = $item->children()->filter('img[class="articles--rows--item__figure-image-img"]')->attr('src');
                        $data['slug'] = $item->attr('data-slug');
                        array_push($all['result'], $data);
                });
                $totalPage = $node->filter('li[class="simple-pagination__page-number"]:last-child')->attr('data-page');
                $all['totalPage'] = $totalPage;
            });
        }
        catch(\Exception $e) {
            $status = false;
        }

        $return = [
            'method' => $method,
            'status' => $status,
            'page' => $page,
            'totalPage' => $all['totalPage'],
            'result' => $all['result'],
        ];

        return json_encode($return);
    }
}
