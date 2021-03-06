<?php 

$data = false;

ini_set("display_errors", 1);
ini_set("error_reporting", E_ALL | E_STRICT);

function registration_callback($username, $email, $userdir)
{
    global $data;
    $data = array($username, $email, $userdir);
}

require_once("user.php");
$USER = new User("registration_callback");
require_once("translate.php");

if(!$USER->authenticated) :

    die("Why you trying to access this without logging in?!?!");

elseif($USER->authenticated && $USER->role !== "admin") :

    die("C'mon man!  I give you access to my stuff and now you're trying to get in the back door?");

endif;

function printArray($arrayName){
    
    foreach ( $arrayName as $item ) :
        
        echo $item . "<br/>";
        
    endforeach;
    
}

function explosion($string, $position){
    
    $getWord = explode("|", $string);
    return $getWord[$position];
    
}

function write_ini_file($content, $path) { 
    
    if (!$handle = fopen($path, 'w')) {
        
        return false; 
    
    }
    
    $success = fwrite($handle, $content);
    
    fclose($handle); 
    
    return $success; 

}

function getServerPath(){
    
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') { 
        
        $protocol = "https://"; 
    
    } else {  
        
        $protocol = "http://"; 
    
    }
    
    return $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['REQUEST_URI']);
      
}

$dbfile = DATABASE_LOCATION  . constant('User::DATABASE_NAME') . ".db";
$databaseLocation = "databaseLocation.ini.php";
$userdirpath = USER_HOME;
$userdirpath = substr_replace($userdirpath, "", -1);

$file_db = new PDO("sqlite:" . $dbfile);
$file_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$getUsers = $file_db->query('SELECT * FROM users');
$gotUsers = $file_db->query('SELECT * FROM users');

$dbTab = $file_db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="tabs"');
$dbOptions = $file_db->query('SELECT name FROM sqlite_master WHERE type="table" AND name="options"');

$tabSetup = "Yes";
$hasOptions = "No";

foreach($dbTab as $row) :

    if (in_array("tabs", $row)) :
    
        $tabSetup = "No";
    
    endif;

endforeach;

foreach($dbOptions as $row) :

    if (in_array("options", $row)) :
    
        $hasOptions = "Yes";
    
    endif;

endforeach;

if($hasOptions == "No") :

    $title = "Organizr";
    $topbar = "#eb6363"; 
    $topbartext = "#FFFFFF";
    $bottombar = "#eb6363";
    $sidebar = "#000000";
    $hoverbg = "#eb6363";
    $activetabBG = "#eb6363";
    $activetabicon = "#FFFFFF";
    $activetabtext = "#FFFFFF";
    $inactiveicon = "#FFFFFF";
    $inactivetext = "#FFFFFF";

endif;

if($tabSetup == "No") :

    $result = $file_db->query('SELECT * FROM tabs');
    
endif;

if($hasOptions == "Yes") :

    $resulto = $file_db->query('SELECT * FROM options');
    
endif;

if($hasOptions == "Yes") : 
                                    
    foreach($resulto as $row) : 

        $title = $row['title'];
        $topbartext = $row['topbartext'];
        $topbar = $row['topbar'];
        $bottombar = $row['bottombar'];
        $sidebar = $row['sidebar'];
        $hoverbg = $row['hoverbg'];
        $activetabBG = $row['activetabBG'];
        $activetabicon = $row['activetabicon'];
        $activetabtext = $row['activetabtext'];
        $inactiveicon = $row['inactiveicon'];
        $inactivetext = $row['inactivetext'];

    endforeach;

endif;

$action = "";
                
if(isset($_POST['action'])) :

    $action = $_POST['action'];
    
endif;

if($action == "deleteDB") : 
                     
    unset($_COOKIE['Organizr']);
    setcookie('Organizr', '', time() - 3600, '/');
    unset($_COOKIE['OrganizrU']);
    setcookie('OrganizrU', '', time() - 3600, '/');

    $file_db = null;

    unlink($dbfile); 

    foreach(glob($userdirpath . '/*') as $file) : 

        if(is_dir($file)) :

            rmdir($file); 

        elseif(!is_dir($file)) :

            unlink($file);

        endif;

    endforeach; 

    rmdir($userdirpath);

   echo "<script>window.parent.location.reload();</script>";

endif;

if($action == "deleteLog") : 
                     
    unlink(FAIL_LOG); 

   echo "<script type='text/javascript'>window.location.replace('settings.php');</script>";

endif;

if($action == "upgrade") : 
                     
    function downloadFile($url, $path){

        $folderPath = "upgrade/";

        if(!mkdir($folderPath)) : echo "can't make dir"; endif;

        $newfname = $folderPath . $path;

        $file = fopen ($url, 'rb');

        if ($file) {

            $newf = fopen ($newfname, 'wb');

            if ($newf) {

                while(!feof($file)) {

                    fwrite($newf, fread($file, 1024 * 8), 1024 * 8);

                }

            }

        }

        if ($file) {

            fclose($file);

        }

        if ($newf) {

            fclose($newf);

        }

    }

    function unzipFile($zipFile){

        $zip = new ZipArchive;

        $extractPath = "upgrade/";

        if($zip->open($extractPath . $zipFile) != "true"){

            echo "Error :- Unable to open the Zip File";
        }

        /* Extract Zip File */
        $zip->extractTo($extractPath);
        $zip->close();

    }

    // Function to remove folders and files 
    function rrmdir($dir) {

        if (is_dir($dir)) {

            $files = scandir($dir);

            foreach ($files as $file)

                if ($file != "." && $file != "..") rrmdir("$dir/$file");

            rmdir($dir);

        }

        else if (file_exists($dir)) unlink($dir);

    }

    // Function to Copy folders and files       
    function rcopy($src, $dst) {

        if (is_dir ( $src )) {

            if (!file_exists($dst)) : mkdir ( $dst ); endif;

            $files = scandir ( $src );

            foreach ( $files as $file )

                if ($file != "." && $file != "..")

                    rcopy ( "$src/$file", "$dst/$file" );

        } else if (file_exists ( $src ))

            copy ( $src, $dst );

    }

    $url = "https://github.com/causefx/Organizr/archive/master.zip";

    $file = "upgrade.zip";

    $source = __DIR__ . "/upgrade/Organizr-master/";

    $cleanup = __DIR__ . "/upgrade/";

    $destination = __DIR__ . "/";

    downloadFile($url, $file);
    unzipFile($file);

    rcopy($source, $destination);
    rrmdir($cleanup);

    echo "<script>window.parent.location.reload(true);</script>";

endif;

if($action == "createLocation") :

    $databaseData = '; <?php die("Access denied"); ?>' . "\r\n";

    foreach ($_POST as $postName => $postValue) {
            
        if($postName !== "action") :
        
            if(substr($postValue, -1) == "/") : $postValue = rtrim($postValue, "/"); endif;
        
            $databaseData .= $postName . " = \"" . $postValue . "\"\r\n";
        
        endif;
        
    }

    write_ini_file($databaseData, $databaseLocation);

endif;
                
if(!isset($_POST['op'])) :

    $_POST['op'] = "";
    
endif; 

if($action == "addTabz") :
    
    if($tabSetup == "No") :

        $file_db->exec("DELETE FROM tabs");
        
    endif;
    
    if($tabSetup == "Yes") :
    
        $file_db->exec("CREATE TABLE tabs (name TEXT UNIQUE, url TEXT, defaultz TEXT, active TEXT, user TEXT, guest TEXT, icon TEXT, iconurl TEXT, window TEXT)");
        
    endif;

    $addTabName = array();
    $addTabUrl = array();
    $addTabIcon = array();
    $addTabIconUrl = array();
    $addTabDefault = array();
    $addTabActive = array();
    $addTabUser = array();
    $addTabGuest = array();
    $addTabWindow = array();
    $buildArray = array();

    foreach ($_POST as $key => $value) :
    
        $trueKey = explode('-', $key);
        
        if ($value == "on") :
        
            $value = "true";
            
        endif;
        
        if($trueKey[0] == "name"):
            
            array_push($addTabName, $value);
            
        endif;
        
        if($trueKey[0] == "url"):
            
            array_push($addTabUrl, $value);
            
        endif;
        
        if($trueKey[0] == "icon"):
            
            array_push($addTabIcon, $value);
            
        endif;

        if($trueKey[0] == "iconurl"):
            
            array_push($addTabIconUrl, $value);
            
        endif;
        
        if($trueKey[0] == "default"):
            
            array_push($addTabDefault, $value);
            
        endif;
        
        if($trueKey[0] == "active"):
            
            array_push($addTabActive, $value);
            
        endif;
        
        if($trueKey[0] == "user"):
            
            array_push($addTabUser, $value);
            
        endif;
        
        if($trueKey[0] == "guest"):
            
            array_push($addTabGuest, $value);
            
        endif; 

        if($trueKey[0] == "window"):
            
            array_push($addTabWindow, $value);
            
        endif;  
        
    endforeach;

    $tabArray = 0;
    
    if(count($addTabName) > 0) : 
        
        foreach(range(1,count($addTabName)) as $index) :
        
            if(!isset($addTabDefault[$tabArray])) :
                
                $tabDefault = "false";
            
            else :
                
                $tabDefault = $addTabDefault[$tabArray];
            
            endif;
            
            $buildArray[] = array('name' => $addTabName[$tabArray],
                  'url' => $addTabUrl[$tabArray],
                  'defaultz' => $tabDefault,
                  'active' => $addTabActive[$tabArray],
                  'user' => $addTabUser[$tabArray],
                  'guest' => $addTabGuest[$tabArray],
                  'icon' => $addTabIcon[$tabArray],
                  'window' => $addTabWindow[$tabArray],
                  'iconurl' => $addTabIconUrl[$tabArray]);

            $tabArray++;
        
        endforeach;
        
    endif; 
    
    $insert = "INSERT INTO tabs (name, url, defaultz, active, user, guest, icon, iconurl, window) 
                VALUES (:name, :url, :defaultz, :active, :user, :guest, :icon, :iconurl, :window)";
                
    $stmt = $file_db->prepare($insert);
    
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':url', $url);
    $stmt->bindParam(':defaultz', $defaultz);
    $stmt->bindParam(':active', $active);
    $stmt->bindParam(':user', $user);
    $stmt->bindParam(':guest', $guest);
    $stmt->bindParam(':icon', $icon);
    $stmt->bindParam(':iconurl', $iconurl);
    $stmt->bindParam(':window', $window);
    
    foreach ($buildArray as $t) :
    
        $name = $t['name'];
        $url = $t['url'];
        $defaultz = $t['defaultz'];
        $active = $t['active'];
        $user = $t['user'];
        $guest = $t['guest'];
        $icon = $t['icon'];
        $iconurl = $t['iconurl'];
        $window = $t['window'];

        $stmt->execute();
        
    endforeach;
    
endif;

if($action == "addOptionz") :
    
    if($hasOptions == "Yes") :
    
        $file_db->exec("DELETE FROM options");
        
    endif;
    
    if($hasOptions == "No") :

        $file_db->exec("CREATE TABLE options (title TEXT UNIQUE, topbar TEXT, bottombar TEXT, sidebar TEXT, hoverbg TEXT, topbartext TEXT, activetabBG TEXT, activetabicon TEXT, activetabtext TEXT, inactiveicon TEXT, inactivetext TEXT)");
        
    endif;
            
    $title = $_POST['title'];
    $topbartext = $_POST['topbartext'];
    $topbar = $_POST['topbar'];
    $bottombar = $_POST['bottombar'];
    $sidebar = $_POST['sidebar'];
    $hoverbg = $_POST['hoverbg'];
    $activetabBG = $_POST['activetabBG'];
    $activetabicon = $_POST['activetabicon'];
    $activetabtext = $_POST['activetabtext'];
    $inactiveicon = $_POST['inactiveicon'];
    $inactivetext = $_POST['inactivetext'];

    $insert = "INSERT INTO options (title, topbartext, topbar, bottombar, sidebar, hoverbg, activetabBG, activetabicon, activetabtext, inactiveicon, inactivetext) 
                VALUES (:title, :topbartext, :topbar, :bottombar, :sidebar, :hoverbg, :activetabBG, :activetabicon , :activetabtext , :inactiveicon, :inactivetext)";
                
    $stmt = $file_db->prepare($insert);
    
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':topbartext', $topbartext);
    $stmt->bindParam(':topbar', $topbar);
    $stmt->bindParam(':bottombar', $bottombar);
    $stmt->bindParam(':sidebar', $sidebar);
    $stmt->bindParam(':hoverbg', $hoverbg);
    $stmt->bindParam(':activetabBG', $activetabBG);
    $stmt->bindParam(':activetabicon', $activetabicon);
    $stmt->bindParam(':activetabtext', $activetabtext);
    $stmt->bindParam(':inactiveicon', $inactiveicon);
    $stmt->bindParam(':inactivetext', $inactivetext);

    $stmt->execute();

    

    
endif;
?>

<!DOCTYPE html>

<html lang="en" class="no-js">

    <head>
        
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="msapplication-tap-highlight" content="no" />

        <title>Settings</title>

        <link rel="stylesheet" href="bower_components/bootstrap/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="bower_components/font-awesome/css/font-awesome.min.css">
        <link rel="stylesheet" href="bower_components/mdi/css/materialdesignicons.min.css">
        <link rel="stylesheet" href="bower_components/metisMenu/dist/metisMenu.min.css">
        <link rel="stylesheet" href="bower_components/Waves/dist/waves.min.css"> 
        <link rel="stylesheet" href="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css"> 

        <link rel="stylesheet" href="js/selects/cs-select.css">
        <link rel="stylesheet" href="js/selects/cs-skin-elastic.css">
        <link href="bower_components/iconpick/dist/css/fontawesome-iconpicker.min.css" rel="stylesheet">
        <link rel="stylesheet" href="bower_components/google-material-color/dist/palette.css">
        
        <link rel="stylesheet" href="bower_components/sweetalert/dist/sweetalert.css">
        <link rel="stylesheet" href="bower_components/smoke/dist/css/smoke.min.css">

        <script src="js/menu/modernizr.custom.js"></script>
        <script type="text/javascript" src="js/sha1.js"></script>
        <script type="text/javascript" src="js/user.js"></script>
        <link rel="stylesheet" href="bower_components/animate.css/animate.min.css">
        <link rel="stylesheet" href="bower_components/DataTables/media/css/jquery.dataTables.css">
        <link rel="stylesheet" href="bower_components/datatables-tabletools/css/dataTables.tableTools.css">

        <link rel="stylesheet" href="css/style.css">
        <link href="css/jquery.filer.css" rel="stylesheet">
	    <link href="css/jquery.filer-dragdropbox-theme.css" rel="stylesheet">

        <!--[if lt IE 9]>
        <script src="bower_components/html5shiv/dist/html5shiv.min.js"></script>
        <script src="bower_components/respondJs/dest/respond.min.js"></script>
        <![endif]-->
        
    </head>

    <body style="padding: 0; background: #273238;">
        
        <style>
        
            input.form-control.material.icp-auto.iconpicker-element.iconpicker-input {
                display: none;
            }input.form-control.iconpicker-search {
                color: black;
            }.key {
    font-family:Tahoma, sans-serif;
    border-style:solid;
    border-color:#D5D6AD #C1C1A8 #CDCBA5 #E7E5C5;
    border-width:2px 3px 8px 3px;
    background:#D6D4B4;
    display:inline-block;
    border-radius:5px;
    margin:3px;
    text-align:center;
}

.key span {
    background:#ECEECA;
    color:#5D5E4F;
    display:block;
    font-size:12px;
    padding:0 2px;
    border-radius:3px;
    width:14px;
    height:18px;
    line-height:18px;
    text-align:center;
    font-weight:bold;
    letter-spacing:1px;
    text-transform:uppercase;
}
.key.wide span {
    width:auto;
    padding:0 12px;
}
        
        </style>
       
        <div id="main-wrapper" class="main-wrapper">

            <!--Content-->
            <div id="content"  style="margin:0 20px; overflow:hidden">
 
                <br/>
                
                <div id="versionCheck"></div>       
            
                <div class="row">
                
                    <div class="col-lg-12">
                  
                        <div class="tabbable tabs-with-bg" id="eighth-tabs">
                    
                            <ul class="nav nav-tabs" style="background: #C0C0C0">
                      
                                <li class="active">
                        
                                    <a href="#tab-tabs" data-toggle="tab"><i class="fa fa-list gray"></i></a>
                      
                                </li>
                      
                                <li>
                        
                                    <a href="#customedit" data-toggle="tab"><i class="fa fa-paint-brush green"></i></a>
                      
                                </li>
                      
                                <li>
                        
                                    <a href="#useredit" data-toggle="tab"><i class="fa fa-user red"></i></a>
                     
                                </li>
                                
                                <li>
                        
                                    <a href="#loginlog" data-toggle="tab"><i class="fa fa-file-text-o indigo"></i></a>
                     
                                </li>
                                
                                <li>
                        
                                    <a href="#systemSettings" data-toggle="tab"><i class="fa fa-cog gray"></i></a>
                     
                                </li>
                                
                                <li>
                        
                                    <a href="#about" data-toggle="tab"><i class="fa fa-info red-orange"></i></a>
                     
                                </li>
    
                            </ul>
                    
                            <div class="tab-content" style="overflow: auto">
                      
                                <div class="big-box todo-list tab-pane big-box  fade in active" id="tab-tabs">

                                    <div class="sort-todo">

                                        <a class="total-tabs"><?php echo $language->translate("TABS");?> <span class="badge gray-bg"></span></a>
                                        
                                        <button id="iconHide" type="button" class="btn waves btn-labeled btn-success btn-sm text-uppercase waves-effect waves-float">
                                            
                                            <span class="btn-label"><i class="fa fa-upload"></i></span><?php echo $language->translate("UPLOAD_ICONS");?>
                                            
                                        </button>
                                        
                                        <button id="iconAll" type="button" class="btn waves btn-labeled btn-success btn-sm text-uppercase waves-effect waves-float">
                                            
                                            <span class="btn-label"><i class="fa fa-picture-o"></i></span><?php echo $language->translate("VIEW_ICONS");?>
                                            
                                        </button>
                                        
                                        <?php if($action) : ?>
                                        
                                        <button id="apply" class="btn waves btn-labeled btn-success btn-sm pull-right text-uppercase waves-effect waves-float" type="submit">
                                        
                                            <span class="btn-label"><i class="fa fa-check"></i></span><?php echo $language->translate("APPLY_CHANGES");?>
                                        
                                        </button>
                                        
                                        <?php endif; ?>

                                    </div>

                                    <input type="file" name="files[]" id="uploadIcons" multiple="multiple">
                                    
                                    <div id="viewAllIcons" style="display: none;">
                                        
                                        <h4><strong><?php echo $language->translate("ALL_ICONS");?></strong> [<?php echo $language->translate("CLICK_ICON");?>]</h4>
                                        
                                        <div class="row">
                                            
                                            <textarea id="copyTarget" class="hideCopy" style="left: -9999px; top: 0; position: absolute;"></textarea>
                                            <?php
                                            $dirname = "images/";
                                            $images = scandir($dirname);
                                            $ignore = Array(".", "..", "favicon/", "favicon", "._.DS_Store", ".DS_Store", "sowwy.png", "sort-btns", "loading.png", "titlelogo.png");
                                            foreach($images as $curimg){
                                                if(!in_array($curimg, $ignore)) { ?>

                                            <div class="col-xs-2" style="width: 75px; height: 75px; padding-right: 0px;">    
                                            
                                                <a class="thumbnail" style="box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2), 0 6px 20px 0 rgba(0, 0, 0, 0.19);">

                                                    <img style="width: 50px; height: 50px;" src="<?=$dirname.$curimg;?>" alt="thumbnail" class="allIcons">

                                                </a>
                                                
                                            </div>

                                            <?php } } ?>

                                        </div>
                                        
                                    </div>
                                    
                                    <form id="add_tab" method="post">

                                        <div class="form-group add-tab">

                                            <div class="input-group">

                                                <div class="input-group-addon">

                                                    <i class="fa fa-pencil gray"></i>

                                                </div>

                                                <input type="text" class="form-control name-of-todo" placeholder="<?php echo $language->translate("TYPE_HIT_ENTER");?>" style="border-top-left-radius: 0;
    border-bottom-left-radius: 0;">

                                            </div>

                                        </div>

                                    </form>

                                    <div class="panel">

                                        <form id="submitTabs" method="post">
                                        
                                            <div class="panel-body todo">

                                                <input type="hidden" name="action" value="addTabz" />

                                                <ul class="list-group ui-sortable">

                                                    <?php if($tabSetup == "No") : $tabNum = 1; 

                                                    foreach($result as $row) : 

                                                    if($row['defaultz'] == "true") : $default = "checked"; else : $default = ""; endif;
                                                    if($row['active'] == "true") : $activez = "checked"; else : $activez = ""; endif;
                                                    if($row['guest'] == "true") : $guestz = "checked"; else : $guestz = ""; endif;
                                                    if($row['user'] == "true") : $userz = "checked"; else : $userz = ""; endif;
                                                    if($row['window'] == "true") : $windowz = "checked"; else : $windowz = ""; endif;

                                                    ?>
                                                    <li id="item-<?=$tabNum;?>" class="list-group-item gray-bg" style="position: relative; left: 0px; top: 0px;">

                                                        <tab class="content-form form-inline">

                                                            <div class="form-group">

                                                                <div class="action-btns" style="width:calc(100%)">

                                                                    <a class="" style="margin-left: 0px"><span class="fa fa-hand-paper-o"></span></a>

                                                                </div>

                                                            </div>

                                                            <div class="form-group">

                                                                <input style="width: 100%;" type="text" class="form-control material input-sm" id="name-<?=$tabNum;?>" name="name-<?=$tabNum;?>" placeholder="<?php echo $language->translate("NEW_TAB_NAME");?>" value="<?=$row['name'];?>">

                                                            </div>

                                                            <div class="form-group">

                                                                <input style="width: 100%;" type="text" class="form-control material input-sm" id="url-<?=$tabNum;?>" name="url-<?=$tabNum;?>" placeholder="<?php echo $language->translate("TAB_URL");?>" value="<?=$row['url']?>">

                                                            </div>

                                                            <div style="margin-right: 5px;" class="form-group">

                                                                <div class="input-group">
                                                                    <input data-placement="bottomRight" class="form-control material icp-auto" name="icon-<?=$tabNum;?>" value="<?=$row['icon'];?>" type="text" />
                                                                    <span class="input-group-addon"></span>
                                                                </div>
                                                                
                                                                - <?php echo $language->translate("OR");?> -

                                                            </div>
                                                            
                                                            <div class="form-group">

                                                                <input style="width: 100%;" type="text" class="form-control material input-sm" id="iconurl-<?=$tabNum;?>" name="iconurl-<?=$tabNum;?>" placeholder="<?php echo $language->translate("ICON_URL");?>" value="<?=$row['iconurl']?>">

                                                            </div>

                                                            <div class="form-group">

                                                                <div class="radio radio-danger">


                                                                    <input type="radio" id="default[<?=$tabNum;?>]" value="true" name="default" <?=$default;?>>
                                                                    <label for="default[<?=$tabNum;?>]"><?php echo $language->translate("DEFAULT");?></label>

                                                                </div>

                                                            </div>

                                                            <div class="form-group">

                                                                <div class="">

                                                                    <input id="" class="switcher switcher-success" value="false" name="active-<?=$tabNum;?>" type="hidden">
                                                                    <input id="active[<?=$tabNum;?>]" class="switcher switcher-success" name="active-<?=$tabNum;?>" type="checkbox" <?=$activez;?>>

                                                                    <label for="active[<?=$tabNum;?>]"></label>

                                                                </div>
                                                                <?php echo $language->translate("ACTIVE");?>
                                                            </div>

                                                            <div class="form-group">

                                                                <div class="">

                                                                    <input id="" class="switcher switcher-primary" value="false" name="user-<?=$tabNum;?>" type="hidden">
                                                                    <input id="user[<?=$tabNum;?>]" class="switcher switcher-primary" name="user-<?=$tabNum;?>" type="checkbox" <?=$userz;?>>
                                                                    <label for="user[<?=$tabNum;?>]"></label>

                                                                </div>
                                                                <?php echo $language->translate("USER");?>
                                                            </div>

                                                            <div class="form-group">

                                                                <div class="">

                                                                    <input id="" class="switcher switcher-primary" value="false" name="guest-<?=$tabNum;?>" type="hidden">
                                                                    <input id="guest[<?=$tabNum;?>]" class="switcher switcher-warning" name="guest-<?=$tabNum;?>" type="checkbox" <?=$guestz;?>>
                                                                    <label for="guest[<?=$tabNum;?>]"></label>

                                                                </div>
                                                                <?php echo $language->translate("GUEST");?>
                                                            </div>
                                                            
                                                            <div class="form-group">

                                                                <div class="">

                                                                    <input id="" class="switcher switcher-primary" value="false" name="window-<?=$tabNum;?>" type="hidden">
                                                                    <input id="window[<?=$tabNum;?>]" class="switcher switcher-danger" name="window-<?=$tabNum;?>" type="checkbox" <?=$windowz;?>>
                                                                    <label for="window[<?=$tabNum;?>]"></label>

                                                                </div>
                                                                <?php echo $language->translate("NO_IFRAME");?>
                                                            </div>

                                                            <div class="pull-right action-btns" style="padding-top: 8px;">

                                                                <a class="trash"><span class="fa fa-close"></span></a>

                                                            </div>


                                                        </tab>

                                                    </li>
                                                    <?php $tabNum ++; endforeach; endif;?>

                                                </ul>

                                            </div>

                                            <div class="checkbox clear-todo pull-left"></div>

                                            <button class="btn waves btn-labeled btn-success btn-sm pull-right text-uppercase waves-effect waves-float" type="submit">
                                                
                                                <span class="btn-label"><i class="fa fa-floppy-o"></i></span><?php echo $language->translate("SAVE_TABS");?>
                                                
                                            </button>
                                            
                                        </form>
                                        
                                    </div>
 
                                </div>

                                <div class="tab-pane big-box  fade in" id="useredit">
                                    
                                    <div class="row">
                                        
                                        <div class="col-lg-12">
                                          
                                            <div class="gray-bg content-box big-box box-shadow">
                                            
                                                <form class="content-form form-inline" name="new user registration" id="registration" action="" method="POST">
                        								    
                                                    <input type="hidden" name="op" value="register"/>
                                                    <input type="hidden" name="sha1" value=""/>

                                                    <div class="form-group">

                                                        <input type="text" class="form-control gray" name="username" placeholder="<?php echo $language->translate("USERNAME");?>" autocorrect="off" autocapitalize="off" value="">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="email" class="form-control gray" name="email" placeholder="<?php echo $language->translate("EMAIL");?>">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="password" class="form-control gray" name="password1" placeholder="<?php echo $language->translate("PASSWORD");?>">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="password" class="form-control gray" name="password2" placeholder="<?php echo $language->translate("PASSWORD_AGAIN");?>">

                                                    </div>
                                                    
                                                    
                                                    
                                                    <button type="submit" onclick="User.processRegistration()" class="btn btn-primary btn-icon waves waves-circle waves-effect waves-float"><i class="fa fa-user-plus"></i></button>

                                                </form>               
                                          
                                            </div>
                                        
                                        </div>
                                      
                                    </div>
                                    
                                    <div class="big-box">
                                        
                                        <form class="content-form form-inline" name="unregister" id="unregister" action="" method="POST">
                                              
                                            <input type="hidden" name="op" value="unregister"/>
                                            
                                            <p id="inputUsername"></p>

                                            <div class="table-responsive">

                                                <table class="table table-striped">

                                                    <thead>

                                                        <tr>

                                                            <th>#</th>

                                                            <th><?php echo $language->translate("USERNAME");?></th>
                                                            
                                                            <th><?php echo $language->translate("EMAIL");?></th>

                                                            <th><?php echo $language->translate("LOGIN_STATUS");?></th>

                                                            <th><?php echo $language->translate("LAST_SEEN");?></th>

                                                            <th><?php echo $language->translate("USER_GROUP");?></th>

                                                            <th><?php echo $language->translate("USER_ACTIONS");?></th>

                                                        </tr>

                                                    </thead>

                                                    <tbody>

                                                        <?php $countUsers = 1; 
                                                        foreach($gotUsers as $row) : 
                                                        if($row['role'] == "admin") : 
                                                            $userColor = "red";
                                                            $disableAction = "disabled=\"disabled\"";
                                                        else : 
                                                            $userColor = "blue";
                                                            $disableAction = "";
                                                        endif;
                                                        if($row['active'] == "true") : 
                                                            $userActive = $language->translate("LOGGED_IN");
                                                            $userActiveColor = "primary";
                                                        else : 
                                                            $userActive = $language->translate("LOGGED_OUT");
                                                            $userActiveColor = "danger";
                                                        endif;
                                                        $userpic = md5( strtolower( trim( $row['email'] ) ) );
                                                        if(!empty($row["last"])) : 
                                                           $lastActive = date("Y-m-d H:i", intval($row["last"]));
                                                        else :
                                                            $lastActive = "";
                                                        endif;
                                                        ?>

                                                        <tr id="<?=$row['username'];?>">

                                                            <th scope="row"><?=$countUsers;?></th>

                                                            <td><i class="userpic"><img src="https://www.gravatar.com/avatar/<?=$userpic;?>?s=25&d=mm" class="img-circle"></i> &nbsp; <?=$row['username'];?></td>
                                                            
                                                            <td><?=$row['email'];?></td>

                                                            <td><span class="label label-<?=$userActiveColor;?>"><?=$userActive;?></span></td>

                                                            <td><?=$lastActive;?></td>

                                                            <td><span class="text-uppercase <?=$userColor;?>"><?=$row['role'];?></span></td>

                                                            <td id="<?=$row['username'];?>">

                                                                <button <?=$disableAction;?> class="btn waves btn-labeled btn-danger btn btn-sm text-uppercase waves-effect waves-float deleteUser">

                                                                    <span class="btn-label"><i class="fa fa-user-times"></i></span><?php echo $language->translate("DELETE");?>

                                                                </button>

                                                            </td>

                                                        </tr>

                                                        <?php $countUsers++; endforeach; ?>

                                                    </tbody>

                                                </table>

                                            </div>
                                            
                                        </form>
                                        
                                    </div>

                                </div>
                                
                                <div class="tab-pane big-box  fade in" id="systemSettings">
                                    
                                    <div class="row">
                                        
                                        <div class="col-lg-12">
                                          
                                            <div class="gray-bg content-box big-box box-shadow">
                                            
                                                <form class="content-form form-inline" name="systemSettings" id="systemSettings" action="" method="POST">
                        								    
                                                    <input type="hidden" name="action" value="createLocation" />

                                                    <div class="form-group">

                                                        <input type="text" class="form-control gray" name="databaseLocation" placeholder="<?php echo $language->translate("DATABASE_PATH");?>" autocorrect="off" autocapitalize="off" value="<?php echo DATABASE_LOCATION;?>">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="text" class="form-control gray" name="timezone" placeholder="<?php echo $language->translate("SET_TIMEZONE");?>" value="<?php echo TIMEZONE;?>">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="text" class="form-control gray" name="titleLogo" placeholder="<?php echo $language->translate("LOGO_URL_TITLE");?>" value="<?php echo TITLELOGO;?>">

                                                    </div>

                                                    <div class="form-group">

                                                        <input type="text" class="form-control gray" name="loadingIcon" placeholder="<?php echo $language->translate("LOADING_ICON_URL");?>" value="<?php echo LOADINGICON;?>">

                                                    </div>
                                                    
                                                    
                                                    
                                                    <button type="submit" class="btn btn-success btn-icon waves waves-circle waves-effect waves-float"><i class="fa fa-floppy-o"></i></button>

                                                </form>               
                                          
                                            </div>
                                        
                                        </div>
                                      
                                    </div>

                                </div>
                                
                                <div class="tab-pane big-box  fade in" id="loginlog">

                                    <div class="table-responsive">

                                        <?php if(file_exists(FAIL_LOG)) : ?>

                                        <div id="loginStats">

                                            <div class="content-box ultra-widget">

                                                <div class="w-progress">

                                                    <span id="goodCount" class="w-amount blue"></span>
                                                    <span id="badCount" class="w-amount red pull-right">3</span>

                                                    <br>

                                                    <span class="text-uppercase w-name"><?php echo $language->translate("GOOD_LOGINS");?></span>
                                                    <span class="text-uppercase w-name pull-right"><?php echo $language->translate("BAD_LOGINS");?></span>

                                                </div>

                                                <div class="progress progress-bar-sm zero-m">

                                                    <div id="goodPercent" class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100" style="width: 20%"></div>

                                                    <div id="badPercent" class="progress-bar progress-bar-danger" role="progressbar" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100" style="width: 80%"></div>

                                                </div>

                                                <div class="w-status clearfix">

                                                    <div id="goodTitle" class="w-status-title pull-left text-uppercase">20%</div>

                                                    <div id="badTitle" class="w-status-number pull-right text-uppercase">80%</div>

                                                </div>

                                            </div>

                                        </div>

                                        <form id="deletelog" method="post">

                                            <input type="hidden" name="action" value="deleteLog" />
                                            <button class="btn waves btn-labeled btn-danger btn-sm pull-right text-uppercase waves-effect waves-float" type="submit">

                                                <span class="btn-label"><i class="fa fa-trash"></i></span><?php echo $language->translate("PURGE_LOG");?>

                                            </button>

                                        </form>

                                        <table id="datatable" class="display">

                                            <thead>

                                                <tr>

                                                    <th><?php echo $language->translate("DATE");?></th>

                                                    <th><?php echo $language->translate("USERNAME");?></th>

                                                    <th><?php echo $language->translate("IP_ADDRESS");?></th>

                                                    <th><?php echo $language->translate("TYPE");?></th>

                                                </tr>

                                            </thead>

                                            <tbody>

                                                <?php

                                                    $getFailLog = str_replace("\r\ndate", "date", file_get_contents(FAIL_LOG));
                                                    $gotFailLog = json_decode($getFailLog, true);
                                                    $goodLogin = 0;
                                                    $badLogin = 0;

                                                    function getColor($colorTest){

                                                        if($colorTest == "bad_auth") :

                                                            $gotColorTest = "danger";

                                                        elseif($colorTest == "good_auth") :

                                                            $gotColorTest = "primary";

                                                        endif;

                                                        echo $gotColorTest;

                                                    }

                                                    foreach (array_reverse($gotFailLog["auth"]) as $key => $val) : 

                                                    if($val["auth_type"] == "bad_auth") : $badLogin++; elseif($val["auth_type"] == "good_auth") : $goodLogin++; endif;
                                                ?>

                                                <tr>

                                                    <td><?=$val["date"];?></td>

                                                    <td><?=$val["username"];?></td>

                                                    <td><?=$val["ip"];?></td>

                                                    <td><span class="label label-<?php getColor($val["auth_type"]);?>"><?=$val["auth_type"];?></span></td>

                                                </tr>

                                                <?php endforeach; ?> 

                                            </tbody>

                                        </table>

                                        <?php 
                                        $totalLogin = $goodLogin + $badLogin;     
                                        $goodPercent = round(($goodLogin / $totalLogin) * 100);
                                        $badPercent = round(($badLogin / $totalLogin) * 100);

                                        endif;

                                        if(!file_exists(FAIL_LOG)) :

                                            echo $language->translate("NOTHING_LOG");

                                        endif;

                                        ?>

                                    </div>

                                </div>
                                
                                <div class="tab-pane big-box  fade in" id="about">
                        
                                    <h4><strong><?php echo $language->translate("ABOUT");?> Organizr</strong></h4>
                        
                                    <p id="version"></p>
                                    
                                    <p id="submitFeedback">
                                    
                                        <a href='https://github.com/causefx/Organizr/issues/new' target='_blank' type='button' class='btn waves btn-labeled btn-success btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-github-alt'></i></span><?php echo $language->translate("SUBMIT_ISSUE");?></a> 
                                        <a href='https://github.com/causefx/Organizr' target='_blank' type='button' class='btn waves btn-labeled btn-primary btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-github'></i></span><?php echo $language->translate("VIEW_ON_GITHUB");?></a>
                                        <a href='https://gitter.im/Organizrr/Lobby' target='_blank' type='button' class='btn waves btn-labeled btn-dark btn text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-comments-o'></i></span><?php echo $language->translate("CHAT_WITH_US");?></a>
                                        <button type="button" class="class='btn waves btn-labeled btn-warning btn text-uppercase waves-effect waves-float" data-toggle="modal" data-target=".Help-Me-modal-lg"><span class='btn-label'><i class='fa fa-life-ring'></i></span><?php echo $language->translate("HELP");?></button>

                                        <div class="modal fade Help-Me-modal-lg" tabindex="-1" role="dialog">
                                        
                                            <div class="modal-dialog modal-lg" role="document">
                                        
                                                <div class="modal-content gray-bg">
                                        
                                                    <div class="modal-header">
                                        
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                        
                                                        <h4 class="modal-title"><?php echo $language->translate("HELP");?>!</h4>
                                        
                                                    </div>
                                        
                                                    <div class="modal-body">
                                        
                                                        <h4><strong><?php echo $language->translate("ADDING_TABS");?></strong></h4>
                                                        
                                                        <p><?php echo $language->translate("START_ADDING_TABS");?></p>
                                                            
                                                        <ul>

                                                            <li><strong><?php echo $language->translate("TAB_URL");?></strong> <?php echo $language->translate("TAB_URL_ABOUT");?></li>
                                                            <li><strong><?php echo $language->translate("ICON_URL");?></strong> <?php echo $language->translate("ICON_URL_ABOUT");?></li>
                                                            <li><strong><?php echo $language->translate("DEFAULT");?></strong> <?php echo $language->translate("DEFAULT_ABOUT");?></li>
                                                            <li><strong><?php echo $language->translate("ACTIVE");?></strong> <?php echo $language->translate("ACTIVE_ABOUT");?></li>
                                                            <li><strong><?php echo $language->translate("USER");?></strong> <?php echo $language->translate("USER_ABOUT");?></li>
                                                            <li><strong><?php echo $language->translate("GUEST");?></strong> <?php echo $language->translate("GUEST_ABOUT");?></li>
                                                            <li><strong><?php echo $language->translate("NO_IFRAME");?></strong> <?php echo $language->translate("NO_IFRAME_ABOUT");?></li>        

                                                        </ul>

                                                        <h4><strong><?php echo $language->translate("QUICK_ACCESS");?></strong></h4>
                                                    
                                                        <p><?php echo $language->translate("QUICK_ACCESS_ABOUT");?> <mark><?php echo getServerPath(); ?>#Sonarr</mark></p>
                                                        
                                                        <h4><strong><?php echo $language->translate("SIDE_BY_SIDE");?></strong></h4>
                                                        
                                                        <p><?php echo $language->translate("SIDE_BY_SIDE_ABOUT");?></p>
                                                        
                                                        <ul>
                                                        
                                                            <li><?php echo $language->translate("SIDE_BY_SIDE_INSTRUCTIONS1");?></li>
                                                            <li><?php echo $language->translate("SIDE_BY_SIDE_INSTRUCTIONS2");?> [<i class='fa fa-refresh'></i>]</li>
                                                            <li><?php echo $language->translate("SIDE_BY_SIDE_INSTRUCTIONS3");?></li>
                                                        
                                                        </ul>

                                                        <h4><strong><?php echo $language->translate("KEYBOARD_SHORTCUTS");?></strong></h4>
                                                    
                                                        <p><?php echo $language->translate("KEYBOARD_SHORTCUTS_ABOUT");?></p>
                                                        
                                                        <ul>
                                                            
                                                            <li><keyboard class="key"><span>S</span></keyboard> + <keyboard class="key"><span>S</span></keyboard> <?php echo $language->translate("KEYBOARD_INSTRUCTIONS1");?></li>
                                                            <li><keyboard class="key wide"><span>Ctrl</span></keyboard> + <keyboard class="key wide"><span>Shift</span></keyboard> + <keyboard class="key"><span>&darr;</span></keyboard> <?php echo $language->translate("KEYBOARD_INSTRUCTIONS2");?></li>
                                                            <li><keyboard class="key wide"><span>Ctrl</span></keyboard> + <keyboard class="key wide"><span>Shift</span></keyboard> + <keyboard class="key"><span>&uarr;</span></keyboard> <?php echo $language->translate("KEYBOARD_INSTRUCTIONS3");?></li>
                                                            <li><keyboard class="key wide"><span>Esc</span></keyboard> + <keyboard class="key wide"><span>Esc</span></keyboard> <?php echo $language->translate("KEYBOARD_INSTRUCTIONS4");?></li>
                                                            
                                                        </ul>
                                                        
                                                    </div>
                                                    
                                                    <div class="modal-footer">
                                        
                                                        <button type="button" class="btn btn-default waves" data-dismiss="modal"><?php echo $language->translate("CLOSE");?></button>
                                        
                                                    </div>
                                        
                                                </div>
                                        
                                            </div>
                                        
                                        </div>
                                    
                                    </p>
                                    
                                    <p id="whatsnew"></p>
                                    
                                    <p id="downloadnow"></p>
                                    
                                    <div class="panel panel-danger">
                                        
                                        <div class="panel-heading">
                                            
                                            <h3 class="panel-title"><?php echo $language->translate("DELETE_DATABASE");?></h3>
                                            
                                        </div>
                                        
                                        <div class="panel-body">
                                            
                                            <div class="">
                                            
                                                <p><?php echo $language->translate("DELETE_WARNING");?></p>
                                                <form id="deletedb" method="post">
                                                    
                                                    <input type="hidden" name="action" value="deleteDB" />
                                                    <button class="btn waves btn-labeled btn-danger pull-right text-uppercase waves-effect waves-float" type="submit">
                                                
                                                        <span class="btn-label"><i class="fa fa-trash"></i></span><?php echo $language->translate("DELETE_DATABASE");?>
                                                
                                                    </button>
                                                    
                                                </form>
                                        
                                            </div>
                                            
                                        </div>
                                        
                                    </div>
                      
                                </div>
                                
                                <div class="tab-pane small-box  fade in" id="customedit">

                                    <form id="add_optionz" method="post">
                                        
                                        <input type="hidden" name="action" value="addOptionz" />
                                        
                                        <div class="btn-group">
                                            
                                            <button type="button" class="btn btn-dark dropdown-toggle btn-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <?php echo $language->translate("CHOOSE_THEME");?>  <span class="caret"></span>
                                            </button>
                                            
                                            <ul class="dropdown-menu gray-bg">
                                            
                                                <li id="plexTheme" style="background: #000000; border-radius: 5px; margin: 5px;"><a style="color: #E49F0C !important;" href="#">Plex</a></li>
                                            
                                                <li id="embyTheme" style="background: #212121; border-radius: 5px; margin: 5px;"><a style="color: #52B54B !important;" href="#">Emby</a></li>
                                                
                                                <li id="bookTheme" style="background: #3B5998; border-radius: 5px; margin: 5px;"><a style="color: #FFFFFF !important;" href="#">Facebook</a></li>
                                                
                                                <li id="spaTheme" style="background: #66BBAE; border-radius: 5px; margin: 5px;"><a style="color: #5B391E !important;" href="#">Spa</a></li>
                                                
                                                <li id="darklyTheme" style="background: #375A7F; border-radius: 5px; margin: 5px;"><a style="color: #FFFFFF !important;" href="#">Darkly</a></li>
                                                
                                                <li id="slateTheme" style="background: #272B30; border-radius: 5px; margin: 5px;"><a style="color: #C8C8C8 !important;" href="#">Slate</a></li>
                                            
                                                <li role="separator" class="divider"></li>
                                            
                                                <li id="defaultTheme" style="background: #eb6363; border-radius: 5px; margin: 5px;"><a style="color: #FFFFFF !important;" href="#"><?php echo $language->translate("DEFAULT");?></a></li>
                                            
                                            </ul>
                                            
                                        </div>
                                        
                                        <button class="btn waves btn-labeled btn-success btn-sm pull-right text-uppercase waves-effect waves-float" type="submit">
                                                
                                                <span class="btn-label"><i class="fa fa-floppy-o"></i></span><?php echo $language->translate("SAVE_OPTIONS");?>
                                                
                                        </button>

                                        <div class="big-box grids">

                                            <div class="row show-grids">

                                                <h4><strong><?php echo $language->translate("TITLE");?></strong></h4>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("TITLE");?></center>

                                                    <input name="title" class="form-control gray" value="<?=$title;?>" placeholder="Organizr">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("TITLE_TEXT");?></center>

                                                    <input name="topbartext" id="topbartext" class="form-control jscolor {hash:true}" value="<?=$topbartext;?>">

                                                </div>

                                            </div>

                                            <div class="row show-grids">

                                                <h4><strong><?php echo $language->translate("NAVIGATION_BARS");?></strong></h4>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("TOP_BAR");?></center>

                                                    <input name="topbar" id="topbar" class="form-control jscolor {hash:true}" value="<?=$topbar;?>">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("BOTTOM_BAR");?></center>

                                                    <input name="bottombar" id="bottombar" class="form-control jscolor {hash:true}" value="<?=$bottombar;?>">

                                                </div>

                                                <div class="clearfix visible-xs-block"></div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("SIDE_BAR");?></center>

                                                    <input name="sidebar" id="sidebar" class="form-control jscolor {hash:true}" value="<?=$sidebar;?>">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("HOVER_BG");?></center>

                                                    <input name="hoverbg" id="hoverbg" class="form-control jscolor {hash:true}" value="<?=$hoverbg;?>">

                                                </div>

                                            </div>

                                            <div class="row show-grids">

                                                <h4><strong><?php echo $language->translate("ACTIVE_TAB");?></strong></h4>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("ACTIVE_TAB_BG");?></center>

                                                    <input name="activetabBG" id="activetabBG" class="form-control jscolor {hash:true}" value=<?=$activetabBG;?>"">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("ACTIVE_TAB_ICON");?></center>

                                                    <input name="activetabicon" id="activetabicon" class="form-control jscolor {hash:true}" value="<?=$activetabicon;?>">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("ACTIVE_TAB_TEXT");?></center>

                                                    <input name="activetabtext" id="activetabtext" class="form-control jscolor {hash:true}" value="<?=$activetabtext;?>">

                                                </div>

                                            </div>

                                            <div class="row show-grids">

                                                <h4><strong><?php echo $language->translate("INACTIVE_TAB");?></strong></h4>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("INACTIVE_ICON");?></center>

                                                    <input name="inactiveicon" id="inactiveicon" class="form-control jscolor {hash:true}" value="<?=$inactiveicon;?>">

                                                </div>

                                                <div class="col-md-2 gray-bg">

                                                    <center><?php echo $language->translate("INACTIVE_TEXT");?></center>

                                                    <input name="inactivetext" id="inactivetext" class="form-control jscolor {hash:true}" value="<?=$inactivetext;?>">

                                                </div>

                                            </div>

                                        </div>
                                        
                                    </form>
                      
                                </div>
                                
                            </div>
                              
                        </div>
                            
                    </div>
                          
                </div>
            
            </div>
            <!--End Content-->

            <!--Welcome notification-->
            <div id="welcome"></div>

        </div>
        <?php if(!$USER->authenticated) : ?>

        <?php endif;?>
        <?php if($USER->authenticated) : ?>

        <?php endif;?>

        <!--Scripts-->
        <script src="bower_components/jquery/dist/jquery.min.js"></script>
        <script src="bower_components/bootstrap/dist/js/bootstrap.min.js"></script>
        <script src="bower_components/metisMenu/dist/metisMenu.min.js"></script>
        <script src="bower_components/Waves/dist/waves.min.js"></script>
        <script src="bower_components/moment/min/moment.min.js"></script>
        <script src="bower_components/jquery.nicescroll/jquery.nicescroll.min.js"></script>
        <script src="bower_components/slimScroll/jquery.slimscroll.min.js"></script>
        <script src="bower_components/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.js"></script>
        <script src="bower_components/cta/dist/cta.min.js"></script>

        <!--Menu-->
        <script src="js/menu/classie.js"></script>
        <script src="bower_components/iconpick/dist/js/fontawesome-iconpicker.js"></script>


        <!--Selects-->
        <script src="js/selects/selectFx.js"></script>
        <script src="js/jscolor.js"></script>
        
        <script src="bower_components/sweetalert/dist/sweetalert.min.js"></script>

        <script src="bower_components/smoke/dist/js/smoke.min.js"></script>

        <!--Notification-->
        <script src="js/notifications/notificationFx.js"></script>

        <script src="js/jqueri_ui_custom/jquery-ui.min.js"></script>
        <script src="js/jquery.filer.min.js" type="text/javascript"></script>
	    <script src="js/custom.js" type="text/javascript"></script>
        
        <!--Data Tables-->
        <script src="bower_components/DataTables/media/js/jquery.dataTables.js"></script>
        <script src="bower_components/datatables.net-responsive/js/dataTables.responsive.js"></script>
        <script src="bower_components/datatables-tabletools/js/dataTables.tableTools.js"></script>

          <script>
            $(function () {
                //Data Tables
                $('#datatable').DataTable({
                    displayLength: 10,
                    dom: 'T<"clear">lfrtip',
                responsive: true,
                    "order": [[ 0, 'desc' ]],
                    "language": {
			           "info": "<?php echo explosion($language->translate('SHOW_ENTRY_CURRENT'), 0);?> _START_ <?php echo explosion($language->translate('SHOW_ENTRY_CURRENT'), 1);?> _END_ <?php echo explosion($language->translate('SHOW_ENTRY_CURRENT'), 2);?> _TOTAL_ <?php echo explosion($language->translate('SHOW_ENTRY_CURRENT'), 3);?>",
                        "infoEmpty": "<?php echo $language->translate('NO_ENTRIES');?>",
                        "infoFiltered": "<?php echo explosion($language->translate('FILTERED'), 0);?> _MAX_ <?php echo explosion($language->translate('FILTERED'), 1);?>",
                        "lengthMenu": "<?php echo $language->translate('SHOW');?> _MENU_ <?php echo $language->translate('ENTRIES');?>",
                        "search": "<?php echo $language->translate('SEARCH');?>",
                        "zeroRecords": "<?php echo $language->translate('NO_MATCHING');?>",
                        "paginate": {
				             "next": "<?php echo $language->translate('NEXT');?>",
                            "previous": "<?php echo $language->translate('PREVIOUS');?>",
				           }
			         }
                });
            });
        </script>
        
        <?php if($_POST['op']) : ?>
        <script>

             $.smkAlert({
                text: '<?php echo printArray($USER->info_log); ?>',
                type: 'info'
            });
            
            <?php if(!empty($USER->error_log)) : ?>
            $.smkAlert({
                position: 'top-left',
                text: '<?php echo printArray($USER->error_log); ?>',
                type: 'warning'
                
            });
            
            <?php endif; ?>
            
        </script>
        <?php endif; ?>
        
        <?php if($action == "addTabz") : ?>
        <script>

            if(!window.location.hash) {
                
                window.location = window.location + '#loaded';
                window.location.reload();
                
            }else{
                
               swal("Tabs Saved!", "Apply Changes To Reload The Page!", "success"); 
                
            }
            
        </script>
        <?php endif; ?>
        
         <?php if($action == "addOptionz") : ?>
        <script>

            swal("Colors Saved!", "Apply Changes To Reload The Page!", "success");
            
        </script>
        <?php endif; ?>

        <script>
            
            (function($) {
            
                function startTrigger(e,data) {
            
                    var $elem = $(this);
            
                    $elem.data('mouseheld_timeout', setTimeout(function() {
            
                        $elem.trigger('mouseheld');
            
                    }, e.data));
                }

                function stopTrigger() {
                
                    var $elem = $(this);
                
                    clearTimeout($elem.data('mouseheld_timeout'));
                }

                var mouseheld = $.event.special.mouseheld = {
                
                    setup: function(data) {
                
                        var $this = $(this);
                
                        $this.bind('mousedown', +data || mouseheld.time, startTrigger);
                
                        $this.bind('mouseleave mouseup', stopTrigger);
                
                    },
                
                    teardown: function() {
                
                        var $this = $(this);
                
                        $this.unbind('mousedown', startTrigger);
                
                        $this.unbind('mouseleave mouseup', stopTrigger);
                
                    },
                
                    time: 200 // default to 750ms
                
                };
                
            })(jQuery);

            $(function () {

                //$(".todo ul").sortable();
                $(".todo ul").sortable({
                    'containment': 'parent',
                    'opacity': 0.9
                });

                $("#add_tab").on('submit', function (e) {
                    e.preventDefault();

                    var $toDo = $(this).find('.name-of-todo');
                    toDo_name = $toDo.val();

                    if (toDo_name.length >= 3) {

                        var newid = $('.list-group-item').length + 1;

                        $(".todo ul").append(
                        '<li id="item-' + newid + '" class="list-group-item gray-bg" style="position: relative; left: 0px; top: 0px;"><tab class="content-form form-inline"> <div class="form-group"><div class="action-btns" style="width:calc(100%)"><a class="" style="margin-left: 0px"><span class="fa fa-hand-paper-o"></span></a></div></div> <div class="form-group"><input style="width: 100%;" type="text" class="form-control material input-sm" name="name-' + newid + '" id="name[' + newid + ']" placeholder="<?php echo $language->translate("NEW_TAB_NAME");?>" value="' + toDo_name + '"></div> <div class="form-group"><input style="width: 100%;" type="text" class="form-control material input-sm" name="url-' + newid + '" id="url[' + newid + ']" placeholder="<?php echo $language->translate("TAB_URL");?>"></div> <div style="margin-right: 5px;" class="form-group"><div class="input-group"><input style="width: 100%;" name="icon-' + newid + '" data-placement="bottomRight" class="form-control material icp-auto" value="fa-diamond" type="text" /><span class="input-group-addon"></span></div> - <?php echo $language->translate("OR");?> -</div>  <div class="form-group"><input style="width: 100%;" type="text" class="form-control material input-sm" id="iconurl-' + newid + '" name="iconurl-' + newid + '" placeholder="<?php echo $language->translate("ICON_URL");?>" value=""></div>  <div class="form-group"> <div class="radio radio-danger"> <input type="radio" name="default" id="default[' + newid + ']" name="default"> <label for="default[' + newid + ']"><?php echo $language->translate("DEFAULT");?></label></div></div> <div class="form-group"><div class=""><input id="" class="switcher switcher-success" value="false" name="active-' + newid + '" type="hidden"><input name="active-' + newid + '" id="active[' + newid + ']" class="switcher switcher-success" type="checkbox" checked=""><label for="active[' + newid + ']"></label></div> <?php echo $language->translate("ACTIVE");?></div> <div class="form-group"><div class=""><input id="" class="switcher switcher-primary" value="false" name="user-' + newid + '" type="hidden"><input id="user[' + newid + ']" name="user-' + newid + '" class="switcher switcher-primary" type="checkbox" checked=""><label for="user[' + newid + ']"></label></div> <?php echo $language->translate("USER");?></div> <div class="form-group"><div class=""><input id="" class="switcher switcher-primary" value="false" name="guest-' + newid + '" type="hidden"><input name="guest-' + newid + '" id="guest[' + newid + ']" class="switcher switcher-warning" type="checkbox" checked=""><label for="guest[' + newid + ']"></label></div> <?php echo $language->translate("GUEST");?></div> <div class="form-group"><div class=""><input id="" class="switcher switcher-primary" value="false" name="window-' + newid + '" type="hidden"><input name="window-' + newid + '" id="window[' + newid + ']" class="switcher switcher-danger" type="checkbox"><label for="window[' + newid + ']"></label></div> <?php echo $language->translate("NO_IFRAME");?></div><div class="pull-right action-btns" style="padding-top: 8px;"><a class="trash"><span class="fa fa-close"></span></a></div></tab></li>'
                        );

                        $('.icp-auto').iconpicker({placement: 'left', hideOnSelect: false, collision: true});

                        var eventObject = {

                            title: $.trim($("#" + newid).text()),
                            className: $("#" + newid).attr("data-bg"),
                            stick: true

                        };

                        $("#" + newid).data('eventObject', eventObject);

                        $toDo.val('').focus();

                    } else {

                        $toDo.focus();
                    }

                });

                count();

                $(".list-group-item").addClass("list-item");

                //Remove one completed item
                $(document).on('click', '.trash', function (e) {

                    var listItemRemove = $(this).closest(".list-group-item");
                    var animation = "zoomOutRight";
                    var container = $(this).closest(".list-group-item");

                    //container.attr('class', 'list-group-item gray-bg animation-container');
                    container.addClass('animated ' + animation);

                    setTimeout(function() {
                        var clearedCompItem = listItemRemove.remove();
                        console.log("removed");
                        e.preventDefault();
                        count();
                    }, 800);
                    

                });

                //Count items
                function count() {

                    var active = $('.list-group-item').length;

                    $('.total-tabs span').text(active);

                };

                $("#submitTabs").on('submit', function (e) {

                    console.log("submitted");

                    $("div.radio").each(function(i) {

                        $(this).find('input').attr('name', 'default-' + i);

                        console.log(i);

                    });

                    $('form input[type="radio"]').not(':checked').each(function() {

                        $(this).prop('checked', true);
                        $(this).prop('value', "false");
                        console.log("found unchecked");

                    });

                });

                $('#apply').on('click touchstart', function(){

                window.parent.location.reload();

                });

            });

        </script>

        <script>
            
            $("#iconHide").click(function(){

                $( "div[class^='jFiler jFiler-theme-dragdropbox']" ).toggle();
     
            });
            
            $("#iconAll").click(function(){

                $( "div[id^='viewAllIcons']" ).toggle();
     
            });
            
            $(".deleteUser").click(function(){

                var parent_id = $(this).parent().attr('id');
                editUsername = $('#unregister').find('#inputUsername');
                $(editUsername).html('<input type="hidden" name="username"value="' + parent_id + '" />');
     
            });
            

            $('.icp-auto').iconpicker({placement: 'left', hideOnSelect: false, collision: true});
            
            $("li[class^='list-group-item']").bind('mouseheld', function(e) {

                $(this).find("span[class^='fa fa-hand-paper-o']").attr("class", "fa fa-hand-grab-o");
                $(this).mouseup(function() {
                    $(this).find("span[class^='fa fa-hand-grab-o']").attr("class", "fa fa-hand-paper-o");
                });
            });
            
            function copyToClipboard(elem) {
                  // create hidden text element, if it doesn't already exist
                var targetId = "_hiddenCopyText_";
                var isInput = elem.tagName === "INPUT" || elem.tagName === "TEXTAREA";
                var origSelectionStart, origSelectionEnd;
                if (isInput) {
                    // can just use the original source element for the selection and copy
                    target = elem;
                    origSelectionStart = elem.selectionStart;
                    origSelectionEnd = elem.selectionEnd;
                } else {
                    // must use a temporary form element for the selection and copy
                    target = document.getElementById(targetId);
                    if (!target) {
                        var target = document.createElement("textarea");
                        target.style.position = "absolute";
                        target.style.left = "-9999px";
                        target.style.top = "0";
                        target.id = targetId;
                        document.body.appendChild(target);
                    }
                    target.textContent = elem.textContent;
                }
                // select the content
                var currentFocus = document.activeElement;
                target.focus();
                target.setSelectionRange(0, target.value.length);

                // copy the selection
                var succeed;
                try {
                      succeed = document.execCommand("copy");
                } catch(e) {
                    succeed = false;
                }
                // restore original focus
                if (currentFocus && typeof currentFocus.focus === "function") {
                    //currentFocus.focus();
                }

                if (isInput) {
                    // restore prior selection
                    elem.setSelectionRange(origSelectionStart, origSelectionEnd);
                } else {
                    // clear temporary content
                    target.textContent = "";
                }
                return succeed;
            }
            
            $("img[class^='allIcons']").click(function(){

                $("textarea[id^='copyTarget']").val($(this).attr("src"));

                copyToClipboard(document.getElementById("copyTarget"));
                
                $.smkAlert({
                
                    text: 'Icon Path Copied To Clipboard',
                
                    type: 'success'
                    
                });
                
                $( "div[id^='viewAllIcons']" ).toggle();
                
            });
         
        </script>
        
        <script>
            
            //Custom Themes            
            function changeColor(elementName, elementColor) {
                
                var definedElement = document.getElementById(elementName);
                
                definedElement.value = elementColor;
                definedElement.style.backgroundColor = elementColor;
                
            }

            $('#plexTheme').on('click touchstart', function(){

                changeColor("topbartext", "#E49F0C");
                changeColor("topbar", "#000000");
                changeColor("bottombar", "#000000");
                changeColor("sidebar", "#121212");
                changeColor("hoverbg", "#FFFFFF");
                changeColor("activetabBG", "#E49F0C");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#949494");
                changeColor("inactivetext", "#B8B8B8");
                
            });
            
            $('#embyTheme').on('click touchstart', function(){

                changeColor("topbartext", "#52B54B");
                changeColor("topbar", "#212121");
                changeColor("bottombar", "#212121");
                changeColor("sidebar", "#121212");
                changeColor("hoverbg", "#FFFFFF");
                changeColor("activetabBG", "#52B54B");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#949494");
                changeColor("inactivetext", "#B8B8B8");
                
            });
            
            $('#bookTheme').on('click touchstart', function(){

                changeColor("topbartext", "#FFFFFF");
                changeColor("topbar", "#3B5998");
                changeColor("bottombar", "#3B5998");
                changeColor("sidebar", "#8B9DC3");
                changeColor("hoverbg", "#FFFFFF");
                changeColor("activetabBG", "#3B5998");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#DFE3EE");
                changeColor("inactivetext", "#DFE3EE");
                
            });
            
            $('#spaTheme').on('click touchstart', function(){

                changeColor("topbartext", "#5B391E");
                changeColor("topbar", "#66BBAE");
                changeColor("bottombar", "#66BBAE");
                changeColor("sidebar", "#C3EEE7");
                changeColor("hoverbg", "#66BBAE");
                changeColor("activetabBG", "#C6C386");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#5B391E");
                changeColor("inactivetext", "#5B391E");
                
            });
            
            $('#darklyTheme').on('click touchstart', function(){

                changeColor("topbartext", "#FFFFFF");
                changeColor("topbar", "#375A7F");
                changeColor("bottombar", "#375A7F");
                changeColor("sidebar", "#222222");
                changeColor("hoverbg", "#464545");
                changeColor("activetabBG", "#FFFFFF");
                changeColor("activetabicon", "#464545");
                changeColor("activetabtext", "#464545");
                changeColor("inactiveicon", "#0CE3AC");
                changeColor("inactivetext", "#0CE3AC");
                
            });
            
            $('#slateTheme').on('click touchstart', function(){

                changeColor("topbartext", "#C8C8C8");
                changeColor("topbar", "#272B30");
                changeColor("bottombar", "#272B30");
                changeColor("sidebar", "#32383E");
                changeColor("hoverbg", "#58C0DE");
                changeColor("activetabBG", "#3E444C");
                changeColor("activetabicon", "#C8C8C8");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#C8C8C8");
                changeColor("inactivetext", "#C8C8C8");
                
            });
            
            $('#defaultTheme').on('click touchstart', function(){

                changeColor("topbartext", "#FFFFFF");
                changeColor("topbar", "#eb6363");
                changeColor("bottombar", "#eb6363");
                changeColor("sidebar", "#000000");
                changeColor("hoverbg", "#eb6363");
                changeColor("activetabBG", "#eb6363");
                changeColor("activetabicon", "#FFFFFF");
                changeColor("activetabtext", "#FFFFFF");
                changeColor("inactiveicon", "#FFFFFF");
                changeColor("inactivetext", "#FFFFFF");
                
            });
        
        </script>
        
        <script>
        
        $( document ).ready(function() {
            
            
            
            $( "div[class^='jFiler jFiler-theme-dragdropbox']" ).hide();
        		
        	$.ajax({
        				
        		type: "GET",
                url: "https://api.github.com/repos/causefx/Organizr/releases/latest",
                dataType: "json",
                success: function(github) {
                   
                    var currentVersion = "0.9998";
                    var githubVersion = github.tag_name;
                    var githubDescription = github.body;
                    var githubName = github.name;
                    infoTabVersion = $('#about').find('#version');
                    infoTabNew = $('#about').find('#whatsnew');
                    infoTabDownload = $('#about').find('#downloadnow');
        
        			if(currentVersion < githubVersion){
                    
                    	console.log("You Need To Upgrade");

                        $.smkAlert({
                            text: '<strong><?php echo $language->translate("NEW_VERSION");?></strong> <?php echo $language->translate("CLICK_INFO");?>',
                            type: 'warning',
                            permanent: true
                        });
                        
                        $(infoTabNew).html("<br/><h4><strong><?php echo $language->translate("WHATS_NEW");?> " + githubVersion + "</strong></h4><strong><?php echo $language->translate("TITLE");?>: </strong>" + githubName + " <br/><strong><?php echo $language->translate("CHANGES");?>: </strong>" + githubDescription);
                        
                        $(infoTabDownload).html("<br/><form style=\"display:initial;\" id=\"deletedb\" method=\"post\"><input type=\"hidden\" name=\"action\" value=\"upgrade\" /><button class=\"btn waves btn-labeled btn-success text-uppercase waves-effect waves-float\" type=\"submit\"><span class=\"btn-label\"><i class=\"fa fa-refresh\"></i></span><?php echo $language->translate("AUTO_UPGRADE");?></button></form> <a href='https://github.com/causefx/Organizr/archive/master.zip' target='_blank' type='button' class='btn waves btn-labeled btn-success text-uppercase waves-effect waves-float'><span class='btn-label'><i class='fa fa-download'></i></span>Organizr v." + githubVersion + "</a>");
                        
                        $( "p[id^='upgrade']" ).toggle();
                    
                    }else if(currentVersion === githubVersion){
                    
                    	console.log("You Are on Current Version");
                        
                        $.smkAlert({
                            text: '<?php echo $language->translate("SOFTWARE_IS");?> <strong><?php echo $language->translate("UP_TO_DATE");?></strong>',
                            type: 'success'
                        });
                    
                    }else{
                    
                    	console.log("something went wrong");

                        $.smkAlert({
                            text: '<strong>WTF!? </strong>Can\'t check version.',
                            type: 'danger',
                            time: 10
                        });
                    
                    }

                    $(infoTabVersion).html("<strong><?php echo $language->translate("INSTALLED_VERSION");?>: </strong>" + currentVersion + " <strong><?php echo $language->translate("CURRENT_VERSION");?>: </strong>" + githubVersion + " <strong><?php echo $language->translate("DATABASE_PATH");?>:  </strong> <?php echo DATABASE_LOCATION;?>");
                    
                }
                
            });
            <?php if(file_exists(FAIL_LOG)) : ?>
            goodCount = $('#loginStats').find('#goodCount');
            goodPercent = $('#loginStats').find('#goodPercent');
            goodTitle = $('#loginStats').find('#goodTitle');
            badCount = $('#loginStats').find('#badCount');
            badPercent = $('#loginStats').find('#badPercent');
            badTitle = $('#loginStats').find('#badTitle');
            $(goodCount).html("<?php echo $goodLogin;?>");            
            $(goodTitle).html("<?php echo $goodPercent;?>%");            
            $(goodPercent).attr('aria-valuenow', "<?php echo $goodPercent;?>");            
            $(goodPercent).attr('style', "width: <?php echo $goodPercent;?>%");            
            $(badCount).html("<?php echo $badLogin;?>");
            $(badTitle).html("<?php echo $badPercent;?>%");            
            $(badPercent).attr('aria-valuenow', "<?php echo $badPercent;?>");            
            $(badPercent).attr('style', "width: <?php echo $badPercent;?>%"); 
            <?php endif; ?>
            
        });
        
        </script>

    </body>

</html>
