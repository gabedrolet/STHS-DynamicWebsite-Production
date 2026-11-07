<?php
require_once "STHSSetting.php";
$TeamInput = (int)0;
$Password = (string)"";
$TeamWebsiteThemeID = (int)0;
$TeamWebsiteLang = (string)"";
$HashMatch = (bool)FALSE;
$Title = (string)"";
$InformationMessage = (string)"";
If (file_exists($DatabaseFile) == false){
	Goto STHSErrorLogin;
}else{try{
	$LeagueName = (string)"";
		
	$db = new SQLite3($DatabaseFile);
	
	$Query = "Select Name, LeagueWebPassword, LeagueWebGuestPassword FROM LeagueGeneral";
	$LeagueGeneral = $db->querySingle($Query,true);		
	$LeagueName = $LeagueGeneral['Name'];
		
	/* Get Team who have the WebPassword Setup */
	$Query = "SELECT Number, Name, GMName FROM TeamProInfo WHERE WebPassword <> \"\" ORDER BY Name";
	$TeamName = $db->query($Query);

	if (isset($_POST["Team"]) && !empty($_POST["Team"]) && isset($_POST["Password"]) && !empty($_POST["Password"])) {
		$TeamInput = filter_var($_POST["Team"], FILTER_SANITIZE_NUMBER_INT);
		$Password = filter_var($_POST["Password"], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW || FILTER_FLAG_STRIP_HIGH || FILTER_FLAG_NO_ENCODE_QUOTES || FILTER_FLAG_STRIP_BACKTICK);
		$TeamWebsiteThemeID = filter_var($_POST["TeamWebsiteThemeID"], FILTER_SANITIZE_NUMBER_INT);
		$TeamWebsiteLang = filter_var($_POST["TeamWebsiteLang"], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW || FILTER_FLAG_STRIP_HIGH || FILTER_FLAG_NO_ENCODE_QUOTES || FILTER_FLAG_STRIP_BACKTICK);	
		
		/* Get Hash */
		If ($TeamInput > 0){
			If ($TeamInput == 101){
				/* League Guest Hash */
				$LeagueGuestCalculateHash = strtoupper(Hash('sha512', mb_convert_encoding(($LeagueName . $Password), 'ASCII')));
				$LeagueDatabaseGuestHash = $LeagueGeneral['LeagueWebGuestPassword'];
				If ($LeagueGuestCalculateHash == $LeagueDatabaseGuestHash && $LeagueDatabaseGuestHash != "" && $LeagueGeneral['LeagueWebGuestPassword'] != ""){ 
					$HashMatch = True;/* Can only match if LeagueWebGuestPassword is not empty */	
					$CookieArray = array(
						'TeamNumber'		=> 101,
						'TeamName'			=> $TopMenuLang['Guest'],
						'TeamGM'			=> '',	
						'TeamWebsiteThemeID'		=> $TeamWebsiteThemeID,
						'TeamWebsiteLang'			=> $TeamWebsiteLang,						
					);						
				}
			}ElseIf ($TeamInput == 102){
				/* League Management Hash */
				$LeagueCalculateHash = strtoupper(Hash('sha512', mb_convert_encoding(($LeagueName . $Password), 'ASCII')));
				$LeagueDatabaseHash = $LeagueGeneral['LeagueWebPassword'];
				If ($LeagueCalculateHash == $LeagueDatabaseHash && $LeagueDatabaseHash != "" && $LeagueGeneral['LeagueWebPassword'] != ""){
					$HashMatch = True;
					$CookieArray = array(
						'TeamNumber'		=> 102,
						'TeamName'			=> $TopMenuLang['LeagueManagement'],
						'TeamGM'			=> '',	
						'TeamWebsiteThemeID'		=> $TeamWebsiteThemeID,
						'TeamWebsiteLang'			=> $TeamWebsiteLang,							
					);						
				}/* Can only match if LeagueWebPassword is not empty */
			}else{
				/* GM Hash */
				$Query = "SELECT Number, Name, GMName, WebPassword FROM TeamProInfo WHERE Number = " . $TeamInput;
				$TeamPasswordCookie = $db->querySingle($Query,true);
				
				/* Confirm GM Hash */
				$GMCalculateHash = strtoupper(Hash('sha512', mb_convert_encoding(($TeamPasswordCookie['GMName'] . $Password), 'ASCII')));
				$GMDatabaseHash = $TeamPasswordCookie['WebPassword'];
				If ($GMCalculateHash == $GMDatabaseHash && $GMDatabaseHash != ""){
					$HashMatch = True;
					$CookieArray = array(
						'TeamNumber'		=> $TeamPasswordCookie['Number'],
						'TeamName'			=> $TeamPasswordCookie['Name'],
						'TeamGM'			=> $TeamPasswordCookie['GMName'],	
						'TeamWebsiteThemeID'		=> $TeamWebsiteThemeID,
						'TeamWebsiteLang'			=> $TeamWebsiteLang,
					);						
				}
			}
		}
		If ($HashMatch == True){
			$CookieTeamNumber = $CookieArray['TeamNumber'];
			$CookieTeamName = $CookieArray['TeamName'];
			$CookieTeamGM = $CookieArray['TeamGM'];
			$CookieTeamWebsiteThemeID = $CookieArray['TeamWebsiteThemeID'];
			$CookieTeamWebsiteLang = $CookieArray['TeamWebsiteLang'];
			$CurrentLink = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			if (strpos($_SERVER['REQUEST_URI'],'?') !== false) {
				$LoginLink = $CurrentLink . "&Logoff";
			}else{
				$LoginLink = $CurrentLink . "?Logoff";
			}			
			
			$encryption_key = base64_decode($CookieTeamNumberKey);
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
			$encrypted = openssl_encrypt(serialize($CookieArray), 'aes-256-cbc', $encryption_key, 0, $iv);
			$CookieArrayDetail = array(
				'expires' => time() + (86400 * 180),
				'path' => '/',
				'domain' => $_SERVER['HTTP_HOST'],
				'secure' => isset($_SERVER['HTTPS']),
				'httponly' => true,
				'samesite' => 'Strict'
			);
			setcookie($Cookie_Name, base64_encode($encrypted . '::' . $iv),$CookieArrayDetail);		
			
			$_COOKIE[$Cookie_Name] = $TeamInput;
			
		}else{
			$InformationMessage = $TopMenuLang['IncorrectPassword'];
		}
	}
	if(isset($_COOKIE[$Cookie_Name]) AND $CookieTeamNumber > 0 ) { //Update Cookie
		if (isset($_POST["TeamWebsiteThemeID"]) && isset($_POST["TeamWebsiteLang"]) && !empty($_POST["TeamWebsiteLang"])) { 
			$CookieTeamWebsiteThemeID = filter_var($_POST["TeamWebsiteThemeID"], FILTER_SANITIZE_NUMBER_INT);
			$CookieTeamWebsiteLang = filter_var($_POST["TeamWebsiteLang"], FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW || FILTER_FLAG_STRIP_HIGH || FILTER_FLAG_NO_ENCODE_QUOTES || FILTER_FLAG_STRIP_BACKTICK);	
			$CookieArray['TeamNumber'] = $CookieTeamNumber;
			$CookieArray['TeamName'] = $CookieTeamName;
			$CookieArray['TeamGM'] = $CookieTeamGM;
			$CookieArray['TeamWebsiteLang'] = $CookieTeamWebsiteLang;
			$CookieArray['TeamWebsiteThemeID'] = $CookieTeamWebsiteThemeID;		
			$encryption_key = base64_decode($CookieTeamNumberKey);
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
			$encrypted = openssl_encrypt(serialize($CookieArray), 'aes-256-cbc', $encryption_key, 0, $iv);
			$CookieArrayDetail = array(
				'expires' => time() + (86400 * 180),
				'path' => '/',
				'domain' => $_SERVER['HTTP_HOST'],
				'secure' => isset($_SERVER['HTTPS']),
				'httponly' => true,
				'samesite' => 'Strict'
			);
			setcookie($Cookie_Name, base64_encode($encrypted . '::' . $iv),$CookieArrayDetail);
			
			If ($CookieTeamWebsiteLang != $lang){
				// Force Language Refresh
				If ($CookieTeamWebsiteLang == "fr"){$lang = "fr";}elseif ($CookieTeamWebsiteLang == "en"){$lang = "en";}else{$lang = "en";}
				If ($lang == "fr"){include 'LanguageFR.php';}else{include 'LanguageEN.php';}
			}
		}
	}} catch (Exception $e) {
	STHSErrorLogin:
		$LeagueName = $DatabaseNotFound;
		$Title = $DatabaseNotFound;
		echo "<style>.STHSLogin_MainDiv{display:none}</style>";
	}
}
include "Header.php";
echo "<title>" . $LeagueName . " - " . $TopMenuLang['Login'] . "</title>";
?>
<script>
 function updateThemeID() {
    var teamSelect = document.getElementById('Team');
    var themeSelect = document.getElementById('TeamWebsiteThemeID');
    var selectedTeamText = teamSelect.options[teamSelect.selectedIndex].text.toLowerCase();

    for (var i = 0; i < themeSelect.options.length; i++) {
      var themeOptionText = themeSelect.options[i].text.toLowerCase();
      if (themeOptionText.includes(selectedTeamText)) {
        themeSelect.selectedIndex = i;
        break;
      }
    }
 } 
</script>
<style>
.SubmitButton {width:100%;}
<?php
foreach ($STHSThemeNumber as $number => $team) {
    if ($number > 1000 && $number < 1100) {
        echo "select[name=\"TeamWebsiteThemeID\"] option[value=\"" . ($number) . "\"] {background-color: var(--TeamNameColor_Background_" . ($number - 1000) . "); color: var(--TeamNameColor_TextColor_" . ($number - 1000) . ");}\n";
    }
}

?>
</style>
</head><body>
<?php include "Menu.php";?>
<div class="STHSLogin_MainDiv" id="FormID" style="width:95%;margin:auto;">
<?php 
Function PrintThemeOption($WebsiteThemeID,$NewsDatabaseFile,$STHSThemeNumber){
	foreach ($STHSThemeNumber as $number => $team) {
		echo "<option";if($WebsiteThemeID == $number){echo " selected=\"selected\"";} echo " value=\"" . $number . "\">" . $team . "</option>\n";
	}
	
	If (file_exists($NewsDatabaseFile) == true){
		$dbNews = new SQLite3($NewsDatabaseFile);
		$Query = "Select * FROM LeagueTheme ORDER BY Number";
		$LeagueTheme = $dbNews->query($Query);
		if (empty($LeagueTheme) == false){while ($Row = $LeagueTheme ->fetchArray()) {		
			echo "<option";if($WebsiteThemeID == $Row['Number']){echo " selected=\"selected\"";} echo " value=\"" . $Row['Number'] . "\">" . $Row['ThemeName'] . "</option>\n";
		}}
	}
}

if(!isset($_COOKIE[$Cookie_Name]) AND $LeagueName != $DatabaseNotFound) {
	/* User Not Login */
	echo "<h1>" . $TopMenuLang['Login'] . "</h1>\n";
	if ($InformationMessage != ""){echo "<div class=\"STHSDivInformationMessage\">" . $InformationMessage . "<br><br></div>\n";}
	
	$page = "" . $_SERVER["REQUEST_URI"] . "";
	echo "<form data-sample=\"1\" data-sample-short=\"\" name=\"frmLogin\" method=\"POST\" action=\"Login.php\">\n";
	echo "<table class=\"STHSPHPLogin_Table\"><tr><td>\n";
	echo "<strong>" . $TopMenuLang['GM'] ."</strong></td><td>\n";
	echo "<select name=\"Team\" id=\"Team\" style=\"width:300px;\" onchange=\"updateThemeID()\">\n";
	if ($LeagueGeneral['LeagueWebPassword'] != ""){	
		echo "<option value=\"102\">" . $TopMenuLang['LeagueManagement'] . "</option>\n";
	}
	if ($LeagueGeneral['LeagueWebGuestPassword'] != ""){	
		echo "<option value=\"101\">" . $TopMenuLang['Guest'] . "</option>\n";
	}		
	if (empty($TeamName) == false){while ($Row = $TeamName ->fetchArray()) {
		echo "<option value=\"" . $Row['Number'] . "\"";if ($Row['Number'] == $TeamInput){echo " selected=\"selected\"";}echo ">" . $Row['Name'] . "</option>\n"; 
	}}
	echo "</select></td></tr>\n";
	echo "<tr><td><strong>" . $TopMenuLang['Password'] . "</strong></td><td><input type=\"password\" name=\"Password\" size=\"20\" style=\"width:300px;\" value=\"\" required></td></tr>\n";
	echo "<tr><td><strong>" . $TopMenuLang['LoginDefaultLanguage'] . "</strong></td><td><select name=\"TeamWebsiteLang\" style=\"width:300px;\"><option value=\"en\">English</option><option";if($lang == "fr"){echo " selected=\"selected\"";} echo " value=\"fr\">Français</option></select></td></tr>\n";
	echo "<tr><td><strong>" . $TopMenuLang['LoginDefaultTheme'] . "</strong></td><td><select name=\"TeamWebsiteThemeID\" id=\"TeamWebsiteThemeID\" style=\"width:300px;\">\n";
	PrintThemeOption($TeamWebsiteThemeID,$NewsDatabaseFile,$STHSThemeNumber);
	echo "</select></td></tr><tr><td></td><td><input class=\"SubmitButton\" type=\"submit\" value=\"" . $TopMenuLang['Login'] . "\">\n";
	echo "</td></tr></table></form>\n";
	echo "<br>" . $TopMenuLang['LoginMessage'];
} else {
	/* User Login */
	echo "<form data-sample=\"1\" data-sample-short=\"\" name=\"frmEdit\" method=\"POST\" action=\"Login.php\">\n";
	echo "<table class=\"STHSPHPLogin_Table\">\n";
	echo "<tr><td colspan=\"2\"><h2>" . $TopMenuLang['CurrentLogin'] . $CookieTeamName ."</h2></td></tr>\n";
	echo "<tr><td><strong>" . $TopMenuLang['LoginDefaultLanguage'] . "</strong></td><td><select name=\"TeamWebsiteLang\" style=\"width:300px;\"><option value=\"en\">English</option><option";if($lang == "fr"){echo " selected=\"selected\"";} echo " value=\"fr\">Français</option></select></td></tr>\n";
	echo "<tr><td><strong>" . $TopMenuLang['LoginDefaultTheme'] . "</strong></td><td><select name=\"TeamWebsiteThemeID\" style=\"width:300px;\">\n";
	PrintThemeOption($CookieTeamWebsiteThemeID,$NewsDatabaseFile,$STHSThemeNumber);	
	echo "</select></td></tr><tr><td><a class=\"SubmitButton\" style=\"padding-right:45px;padding-left:45px;\" href=\"" . $LoginLink . "\">". $TopMenuLang['Logout'] . "</a></td><td><input class=\"SubmitButton\" type=\"submit\" value=\"" . $TopMenuLang['CustomizeWebsite'] . "\">\n";
	echo "</td></tr>";
	echo "<tr><td></td><td><br><br><a class=\"SubmitButton\" style=\"padding-right:45px;padding-left:45px;\" href=\"ThemeEditor.php\">" . $TopMenuLang['CustomTheme'] . "</a>";
	echo "</table></form>\n";
}

?>
</div>
<?php include "Footer.php";?>