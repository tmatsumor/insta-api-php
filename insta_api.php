<?php
require_once('http-requests-php/http_requests.php');

class InstaAPI extends HttpRequests
{
    const INSTA_URL = 'https://graph.instagram.com/';
    const IDS_FILE  = 'insta_post_ids.txt';
    private $token;

    public function __construct($long_lived_token) {
        $this->get(self::INSTA_URL.'refresh_access_token',      // refresh long-lived-access-token
            ['grant_type'=>'ig_refresh_token','access_token'=>$long_lived_token]);
        $this->token = $long_lived_token;
        touch(self::IDS_FILE);                              // create ids file if it doesn't exist
    }

    public function fetchNewPost(Closure $fn) {
        $res = $this->get(self::INSTA_URL.'me/media',                           // fetch media data
            ['fields'=>'id,media_type,media_url,caption,permalink','access_token'=>$this->token]);
        $row = array_slice(json_decode($res[0], TRUE)['data'], 0, 10);                // fetch rows
        $nid = array_map(fn($x) => $x['id'], $row);                     // get current new post ids
        $oid = explode(',', trim(file_get_contents(self::IDS_FILE)));           // get old post ids
        $dif = array_diff($nid, $oid);                                      // diff new and old ids
        foreach($dif as $a){
            $r = array_values(array_filter($row, fn($x) => $x['id'] === $a))[0];// first matched el
            $fn($r);                                                // call a function in arguments
            sleep(2);
        }
        file_put_contents(self::IDS_FILE, implode(',', array_slice($nid, 0, 10)));
    }
}
