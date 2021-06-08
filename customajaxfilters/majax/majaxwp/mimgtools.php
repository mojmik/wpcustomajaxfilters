<?php 
namespace CustomAjaxFilters\Majax\MajaxWP;

//usage: /mimgtools.php?img=https://www.doruceni.cz/wp-content/uploads/kn95-300x300.jpg
//usage: opt2 /mimgtools.php?id=31935
//UPDATE wp_post SET post_content = REPLACE(post_content,'width="300" src=''http','width="300" src=''/mimgtools.php?img=http') WHERE ID=31935;
//SELECT * FROM wp_posts  WHERE ID=31935
//UPDATE wp_post SET post_content = REPLACE(post_content,'width="300" src=''http',CONCAT('width="300" src=''/mimgtools.php?img=',`post_id`) WHERE ID=31935;
//todo: projet vsechny hp_listings, upravit img src, vygenerovat mimgnfo a je to; dalsi pokus by mohl byt vypisovat ten obrazek javascriptem

Class MimgTools {
	
	public static function handleRequest() {
		$mImgTools=get_query_var("mimgtools");		
		$url=get_query_var("url");	
		MimgTools::prepImage($mImgTools,$url);	
				
	}
	static function prepImage($postId="",$url="") {
		if ($postId) {
			$filename = "./wp-content/uploads/mimgnfo-$postId";
			if (file_exists($filename)) {
				$url=file_get_contents($filename);		
				$filename = "./wp-content/uploads/mimg-".basename(parse_url($url, PHP_URL_PATH));  
				//echo "cont:".$filename;
			}
		}
		else if ($url) {
			$filename = "./wp-content/uploads/mimg-".basename(parse_url($url, PHP_URL_PATH));  
		}
		else return "";  
		
		if (file_exists($filename)) {
			echo file_get_contents($filename);
		}
		else {
			$image = ImageCreateFromString(file_get_contents($url));  
			if ($image) {
				// calculate resized ratio
				// Note: if $height is set to TRUE then we automatically calculate the height based on the ratio
				$height=true;
				$width=600;
				$height = $height === true ? (ImageSY($image) * $width / ImageSX($image)) : $height;
				
				// create image 
				$output = ImageCreateTrueColor($width, $height);
				ImageCopyResampled($output, $image, 0, 0, 0, 0, $width, $height, ImageSX($image), ImageSY($image));
				// save image
				//$filename="./wp-content/uploads/mimg-$url";	  
				
				ImageJPEG($output, $filename, 95); 
				// return resized image	  
				echo file_get_contents($filename);
			}
		}
		die();
	}

}
?>