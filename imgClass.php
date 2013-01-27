<?php

class imgClass {

    private $items;
    private $dbh;
    private $imgDir;
    private $imgFile;
    private $imgThumb;
    private $userID;
    private $imgName;
    private $ImgExt;
    private $imageResized;
    private $image;
    private $width;
    private $height;
    
    private $mimeTypes = array( 'image/gif',
                                'image/png',
                                'image/jpeg'
    );
    
    private $imgtypeConstants = array(  IMAGETYPE_GIF,
                                        IMAGETYPE_JPEG,
                                        IMAGETYPE_PNG,
                                        IMAGETYPE_BMP                                    
    );



    function __construct($dbh, $imgDir) {

        // Check for params
        if (!isset($dbh) || empty($dbh)) {
            throw new Exception('Database handler is empty');
        }
        if (!isset($imgDir) || empty($imgDir)) {
            throw new Exception('Image directory is empty');
        }
        if (!is_dir($imgDir)) {
            throw new Exception('Image directory is not a directory');
        }
        if ( !is_writable($imgDir) ) {
            throw new Exception('Image directory is not writable');
        }

        $this->dbh    = $dbh;
        $this->imgDir = $imgDir;
    }

    private function DB_Execute( $sql, $exParams = array() ) {
        $sth = $this->dbh->prepare( $sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
        $sth->execute( $exParams );

        if ( stristr($sql,'INSERT') || stristr($sql,'UPDATE') || stristr($sql,'DELETE') )  {
            return $this->dbh->lastInsertId();
        } else {
            $result = $sth->fetchAll();

            if ( count ( $result ) > 0 ) {
                return $result;
            } else {
                return false;
            }
        }
    }

    public function fileCheck($file) {

        if ( $file['file']['error'] != UPLOAD_ERR_OK ) {
            $errorMessage = 'Image did not upload correctly: ';

            // Handle all of the error values
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    $errorMessage .= 'File size is greater then php.ini allows.';
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMessage .= 'File size is greater then form size allows.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMessage .= 'No /tmp directory to upload to.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMessage .= 'Can not write to /tmp directory.';
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $errorMessage .= 'Extension is not valid or unknown.';
                    break;

                default:
                    $errorMessage .= 'No image uploaded';
                    break;
            }

            throw new Exception($errorMessage);
        }
        if (!getimagesize($file['file']['tmp_name']) ) {
            throw new Exception("Image is not an image at all.");
        }
        
        $imgInfo = getimagesize($file['file']['tmp_name']);
        if ( $imgInfo[0] < 50 || $imgInfo[1] < 50) {
            throw new Exception("Image is too small, or not an image at all.");
        }
        
        if ( !is_uploaded_file($file['file']['tmp_name']) ) {
            throw new Exception("File was not uploaded, stop playing les buggeures risible.");
        }        
        
        if ( CHECK_SCRIPT ) {
            $bytes = file_get_contents($file['file']['tmp_name']);
            if ( stristr($bytes,"php") ) {
                throw new Exception("Go away Annex");
            }
        }
    
        if ( CHECK_MIME ) {
            if ( !in_array( $file['file']['type'], $this->mimeTypes ) ) {
                throw new Exception('Uploaded file is not an image.');
            }
            
            if ( !in_array( $imgInfo['mime'], $this->mimeTypes ) ) {
                throw new Exception('Uploaded file is not an image.');
            }
        }
        
        $this->imgFile = $file;

        return true;
    }
 
    public function moveImg() {
        
        $this->imgName = uniqid();
        $this->getExtension();
        $this->createThumb();
        
    }

    private function createThumb() {

        $this->image  = $this->openImage( $this->imgFile['file']['tmp_name'] );
        $this->width  = imagesx($this->image);
        $this->height = imagesy($this->image);
        
        // Instead of moving the uploaded file
        //  we open the image with the GD library, then save it with 90% quality
        //  This effectily strips any junk code uploaded via exploits
        //
        // Thanks to Annex for bringing this to my attention
        
        $this->resizeImage($this->width, $this->height);        
        $this->saveImage(sprintf("%s/%s.%s",   $this->imgDir, $this->imgName, $this->imgExt), 90);

        $this->resizeImage(360, 268, 'crop');        
        $this->saveImage(sprintf("%s/%s.md.%s",$this->imgDir, $this->imgName, $this->imgExt), 75);

        $this->resizeImage(160, 120, 'crop');        
        $this->saveImage(sprintf("%s/%s.sm.%s",$this->imgDir, $this->imgName, $this->imgExt), 75);
    }

    private function openImage($file)    {
        switch($this->imgExt)        {
            case 'jpg':
                $img = imagecreatefromjpeg($file);
                break;
            case 'gif':
                $img = imagecreatefromgif($file);
                break;
            case 'png':
                $img = imagecreatefrompng($file);
                break;
            default:
                throw new Exception('Could not shrink image, is file a valid image?');
                break;
        }
        return $img;
    }

    private function getExtension() {
        $ext = "";
        switch($this->imgFile['file']['type']) {
            case "image/jpeg":
                $ext = "jpg";
                break;
            case "image/png":
                $ext = "png";
                break;
            case "image/gif":
                $ext = "gif";
                break;
            default:
                throw new Exception('Image is not valid, even after passing 3 checks so far.');
                break;
        }
        $this->imgExt = $ext;
    }

    public function attachImg($pass) {
        $pass = hash( 'sha256', $pass . PASS_SALT );
        
        $sql = "INSERT INTO `images` ( `imageName`, `imageExt`, `uploadDate`, `uploadIP`, `password` )
                VALUES ( ?, ?, NOW(), ?, ? );";

        if ($result = $this->DB_Execute($sql, array( $this->imgName, $this->imgExt, $_SERVER['REMOTE_ADDR'], $pass ) )) {
            $this->userID = $result;
        } else {
            throw new Exception('Could not attach image to user in database.');
        }
        return true;
    }

    public function goToImageInfo() {
        header( sprintf("location: ./i/%s.%s", $this->imgName, $this->imgExt ));
    }

    public function getRecent() {
        $return = array();

        $sql = "SELECT      `imageName` ,`imageExt`
                FROM        `images`
                ORDER BY    `uploadDate`
                DESC
                LIMIT 8";

        if ($result = $this->DB_Execute($sql)) {
            foreach($result as $row) {
                $return[] = array(  'name' => $row['imageName'],
                                    'ext'  => $row['imageExt']
                );
            }
        } 
        return $return;
    }
    public function getImages($page, $display = 18) {
        $return = array();
        $return['links'] = array();

        $sql = "SELECT      count(*) as total
                FROM        `images`";
        if ($result = $this->DB_Execute($sql)) {
            foreach($result as $row) {
                $return['total'] = $row['total'];
            }
            
            $numrows     = $return['total'];
            $rowsperpage = $display;
            $totalpages  = ceil($numrows / $rowsperpage);
            $range       = 3;
            
            if (isset($page) && is_numeric($page)) {
               $currentpage = $page;
            } else {
               $currentpage = 1;
            } 
            
            if ($currentpage > $totalpages) {
               $currentpage = $totalpages;
            } 
            if ($currentpage < 1) {            
               $currentpage = 1;
            } 
            
            $offset = ($currentpage - 1) * $rowsperpage;

            
            $sql = sprintf("SELECT      `imageName` ,`imageExt`
                            FROM        `images`
                            ORDER BY    `uploadDate`
                            DESC
                            LIMIT %d, %d",
                            intval($offset),
                            intval($rowsperpage)
            );

            if ($result = $this->DB_Execute($sql)) {
                foreach($result as $row) {
                    $return['images'][] = array('name' => $row['imageName'],
                                                'ext'  => $row['imageExt']
                    );
                }
            }
            

            if ($currentpage > 1) {
                $return['links'][] = array( 'text' => '«', 
                                            'link' => '../1/');
                $prevpage = $currentpage - 1;
                $return['links'][] = array( 'text' => 'Prev', 
                                            'link' => '../'.$prevpage.'/');
            } 

            for ($x = ($currentpage - $range); $x < (($currentpage + $range) + 1); $x++) {
                if (($x > 0) && ($x <= $totalpages)) {
                    if ($x == $currentpage) {
                        $return['links'][] = array( 'text' =>  $x, 
                                                     'link' => '#',
                                                     'active' => true);
                    } else {
                        $return['links'][] = array( 'text' =>  $x, 
                                                    'link' => '../'.$x.'/');
                    } 
                }
            }
    
            if ($currentpage != $totalpages) {              
                $nextpage = $currentpage + 1;              
                $return['links'][] = array( 'text' => 'Next', 
                                            'link' => '../'.$nextpage.'/');
                $prevpage = $currentpage - 1;
                $return['links'][] = array( 'text' => '»', 
                                            'link' => '../'.$totalpages.'/');
            }             
        }
        return $return;
    }
    public function deleteImage($imgID,$pass) {
        $pass = hash( 'sha256', $pass . PASS_SALT );
        
        $sql = "DELETE 
                FROM     `images`
                WHERE     `imageName` = ?
                AND        `password` = ?";
        if ($result = $this->DB_Execute($sql, array($imgID, $pass))) {
            return true;
        } else {
            return false;
        }        
    }
    public function display($imgID) {
        $sql = "SELECT      `imageName` ,`imageExt`, `hits`, `uploadDate`
                FROM        `images`
                WHERE       `imageName` = ?
                ORDER BY    `uploadDate`
                DESC
                LIMIT 1";

        if ( $result = $this->DB_Execute($sql, array($imgID)) ) {

            foreach($result as $row) {
                $hits = $row['hits'];
                $name = $row['imageName'];
                $ext  = $row['imageExt'];
                $date = $this->ago($row['uploadDate']);
            }
            
            $hits++;

            $sql = "UPDATE  `images`
                    SET     `hits` = ?
                    WHERE   `imageName` = ?";
            $this->DB_Execute( $sql, array($hits, $imgID) );

            return array(   'dir'  => $this->imgDir,
                            'name' => $name,
                            'ext'  => $ext,
                            'date' => $date,
                            'hits' => $hits
            );
        } else {
            header("location: ../index.php");
        }
    }
    private function getDimensions($newWidth, $newHeight, $option)    {
       switch ($option)        {
            case 'exact':
                $optimalWidth = $newWidth;
                $optimalHeight= $newHeight;
                break;
            case 'portrait':
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight= $newHeight;
                break;
            case 'landscape':
                $optimalWidth = $newWidth;
                $optimalHeight= $this->getSizeByFixedWidth($newWidth);
                break;
            case 'auto':
                $optionArray = $this->getSizeByAuto($newWidth, $newHeight);
                $optimalWidth = $optionArray['optimalWidth'];
                $optimalHeight = $optionArray['optimalHeight'];
                break;
            case 'crop':
                $optionArray = $this->getOptimalCrop($newWidth, $newHeight);
                $optimalWidth = $optionArray['optimalWidth'];
                $optimalHeight = $optionArray['optimalHeight'];
                break;
        }
        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }
    private function resizeImage($newWidth, $newHeight, $option="auto")    {
        $optionArray = $this->getDimensions($newWidth, $newHeight, strtolower($option));
        $optimalWidth  = $optionArray['optimalWidth'];
        $optimalHeight = $optionArray['optimalHeight'];
        $this->imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
        imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);
        if ($option == 'crop') {
            $this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight);
        }
    }
    private function saveImage($savePath, $imageQuality="100")    {
        $extension = strrchr($savePath, '.');
        $extension = strtolower($extension);
        switch($extension) {
            case '.jpg':
            case '.jpeg':
                if (imagetypes() & IMG_JPG) {
                    imagejpeg($this->imageResized, $savePath, $imageQuality);
                }
                break;

            case '.gif':
                if (imagetypes() & IMG_GIF) {
                    imagegif($this->imageResized, $savePath);
                }
                break;

            case '.png':
                $scaleQuality = round(($imageQuality/100) * 9);
                $invertScaleQuality = 9 - $scaleQuality;
                if (imagetypes() & IMG_PNG) {
                    imagepng($this->imageResized, $savePath, $invertScaleQuality);
                }
                break;
            default:
                break;
        }
        imagedestroy($this->imageResized);
    }
    private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight)    {
        $cropStartX = ( $optimalWidth / 2) - ( $newWidth /2 );
        $cropStartY = ( $optimalHeight/ 2) - ( $newHeight/2 );

        $crop = $this->imageResized;
        $this->imageResized = imagecreatetruecolor($newWidth , $newHeight);
        imagecopyresampled($this->imageResized, $crop , 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight , $newWidth, $newHeight);
    }
    private function getSizeByFixedHeight($newHeight)    {
        $ratio = $this->width / $this->height;
        $newWidth = $newHeight * $ratio;
        return $newWidth;
    }
    private function getSizeByFixedWidth($newWidth)    {
        $ratio = $this->height / $this->width;
        $newHeight = $newWidth * $ratio;
        return $newHeight;
    }
    private function getSizeByAuto($newWidth, $newHeight)    {
        if ($this->height < $this->width)        {
            $optimalWidth = $newWidth;
            $optimalHeight= $this->getSizeByFixedWidth($newWidth);
        }
        elseif ($this->height > $this->width)        {
            $optimalWidth = $this->getSizeByFixedHeight($newHeight);
            $optimalHeight= $newHeight;
        }
        else        {
            if ($newHeight < $newWidth) {
                $optimalWidth = $newWidth;
                $optimalHeight= $this->getSizeByFixedWidth($newWidth);
            } else if ($newHeight > $newWidth) {
                $optimalWidth = $this->getSizeByFixedHeight($newHeight);
                $optimalHeight= $newHeight;
            } else {
                $optimalWidth = $newWidth;
                $optimalHeight= $newHeight;
            }
        }
        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }
    private function getOptimalCrop($newWidth, $newHeight)    {
        $heightRatio = $this->height / $newHeight;
        $widthRatio  = $this->width /  $newWidth;
        if ($heightRatio < $widthRatio) {
            $optimalRatio = $heightRatio;
        } else {
            $optimalRatio = $widthRatio;
        }
        $optimalHeight = $this->height / $optimalRatio;
        $optimalWidth  = $this->width  / $optimalRatio;
        return array('optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight);
    }
    private function ago($d) {
         $c = getdate();
         $p = array('year', 'mon', 'mday', 'hours', 'minutes', 'seconds');
         $display = array('year', 'month', 'day', 'hour', 'minute', 'second');
         $factor = array(0, 12, 30, 24, 60, 60);
         $d = $this->datetoarr($d);
         for ($w = 0; $w < 6; $w++) {
            if ($w > 0) {
                $c[$p[$w]] += $c[$p[$w-1]] * $factor[$w];
                $d[$p[$w]] += $d[$p[$w-1]] * $factor[$w];
            }
            if ($c[$p[$w]] - $d[$p[$w]] > 1) { 
                return ($c[$p[$w]] - $d[$p[$w]]).' '.$display[$w].'s ago';
            }
         }
         return '';
    }     
    private function datetoarr($d) {
        preg_match("/([0-9]{4})(\\-)([0-9]{2})(\\-)([0-9]{2}) ([0-9]{2})(\\:)([0-9]{2})(\\:)([0-9]{2})/", $d, $matches);
        return array( 
            'seconds' => $matches[10], 
            'minutes' => $matches[8], 
            'hours'   => $matches[6],  
            'mday'    => $matches[5], 
            'mon'     => $matches[3],  
            'year'    => $matches[1], 
         );
    }
    function __destruct() {}
}

?>
