<?php
namespace CustomAjaxFilters\Majax\MajaxWP;

Class MajaxItem {	
    private $metaFields=array();
    private $mainFields=array();  
    public function addMeta($metaKey,$metaValue) {
        $this->metaFields[$metaKey]=($metaValue);
        return $this;
    }    
    public function addField($fieldKey,$fieldValue) {
        $this->mainFields[$fieldKey]=($fieldValue);
        return $this;
    }
    public function expose($getJson=1) {        
        $arr=$this->mainFields;
        $arr["meta"]=$this->metaFields;
        if ($getJson) return json_encode($arr);        
        else return $arr;
    }
    public function shrinkField($fieldKey) {
      $val=self::excerpt_paragraph($this->mainFields[$fieldKey]);
      //$val=$this->mainFields[$fieldKey];
      $this->mainFields[$fieldKey]=$val;
    }

    public function getDataExternalTable($table) {
        //load data from external table
        //SELECT * FROM wp_majax_postType_externalTable WHERE fieldId=fieldId AND postId=id     
        //$rows=MajaxWP\Caching::getCachedRows($query);
        //tohle udelam jinak- bude to tahat data z jinyho customposttypu

        /*
        var 1)
        1) dalsi info proste natahnu pri build items
        2) kapacity (filtrovaci meta), ty bud resit jako dalsi meta nebo taky jako extra tabulka
        

            SELECT post_title,post_name,post_content,pm1.`mauta_destinace`,pm1.`mauta_termin`,pm1.`mauta_delka`,pm1.`mauta_strava`,pm1.`mauta_cena-od`,pm1.`_thumbnail_id`  
            FROM
            (
                 SELECT post_title,post_name,post_content,MAX(CASE WHEN pm1.meta_key = 'mauta_destinace' then pm1.meta_value ELSE NULL END) as `mauta_destinace`,MAX(CASE WHEN pm1.meta_key = 'mauta_termin' then pm1.meta_value ELSE NULL END) as `mauta_termin`,MAX(CASE WHEN pm1.meta_key = 'mauta_delka' then pm1.meta_value ELSE NULL END) as `mauta_delka`,MAX(CASE WHEN pm1.meta_key = 'mauta_strava' then pm1.meta_value ELSE NULL END) as `mauta_strava`,MAX(CASE WHEN pm1.meta_key = 'mauta_cena-od' then pm1.meta_value ELSE NULL END) as `mauta_cena-od`,MAX(CASE WHEN pm1.meta_key = '_thumbnail_id' then pm1.meta_value ELSE NULL END) as `_thumbnail_id`

                        FROM wp_posts LEFT JOIN wp_postmeta pm1 ON ( pm1.post_id = ID)  
                        WHERE post_id=id 
                        AND post_status like 'publish' 
                        AND post_type like 'zajezd'			
                        GROUP BY ID, post_title
            ) 
            AS pm1
            ORDER BY cast(pm1.`mauta_cena-od` AS unsigned) ASC
        ===
        tohle se hodi treba na zjistovani kapacit (filtrovatelnych poli)
        na obrazky (nefiltrovana pole) asi extra dotaz

            SELECT post_title,post_name,post_content,pm1.`mauta_destinace`,pm1.`mauta_termin`,pm1.`mauta_delka`,pm1.`mauta_strava`,pm1.`mauta_cena-od`,pm1.`_thumbnail_id`,pm2.`opt`,pm2.`val`
            FROM
            (
                SELECT post_title,post_name,post_content,MAX(CASE WHEN pm1.meta_key = 'mauta_destinace' then pm1.meta_value ELSE NULL END) as `mauta_destinace`,MAX(CASE WHEN pm1.meta_key = 'mauta_termin' then pm1.meta_value ELSE NULL END) as `mauta_termin`,MAX(CASE WHEN pm1.meta_key = 'mauta_delka' then pm1.meta_value ELSE NULL END) as `mauta_delka`,MAX(CASE WHEN pm1.meta_key = 'mauta_strava' then pm1.meta_value ELSE NULL END) as `mauta_strava`,MAX(CASE WHEN pm1.meta_key = 'mauta_cena-od' then pm1.meta_value ELSE NULL END) as `mauta_cena-od`,MAX(CASE WHEN pm1.meta_key = '_thumbnail_id' then pm1.meta_value ELSE NULL END) as `_thumbnail_id`
        
                    FROM wp_posts LEFT JOIN wp_postmeta pm1 ON ( pm1.post_id = ID)  
                    WHERE post_id=id 
                    AND post_status like 'publish' 
                    AND post_type like 'zajezd'			
                    GROUP BY ID, post_title
            ) 
            AS pm1
        
            LEFT JOIN wp_mauta_zajezd_external pm2 ON (pm2.postId = post_title)
            WHERE pm2.`opt` = 'datum'		
            ORDER BY cast(pm1.`mauta_cena-od` AS unsigned) ASC

            */


    }

    public static function excerpt_paragraph($html, $max_char = 100, $trail='...' ) {
        // temp var to capture the p tag(s)
        $matches= array();
        if ( preg_match( '/<p>[^>]+<\/p>/', $html, $matches) )
        {
            // found <p></p>
            $p = strip_tags($matches[0]);
        } else {
            $p = strip_tags($html);
        }
        //shorten without cutting words
        $p = self::short_str($p, $max_char );

        // remove trailing comma, full stop, colon, semicolon, 'a', 'A', space
        $p = rtrim($p, ',.;: aA' );

        // return nothing if just spaces or too short
        if (ctype_space($p) || $p=='' || strlen($p)<10) { return ''; }

        return '<p>'.$p.$trail.'</p>';
    }
    /**
    * shorten string but not cut words
    * 
    **/
    public static function short_str( $str, $len, $cut = false )    {
        if ( strlen( $str ) <= $len ) { return $str; }
        $string = ( $cut ? substr( $str, 0, $len ) : substr( $str, 0, strrpos( substr( $str, 0, $len ), ' ' ) ) );
        return $string;
    }
}
