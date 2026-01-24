<?php If (isset($SearchLang) == False){include 'LanguageEN.php';} if (isset($Year) == False){$Year=-1;$Playoff=False;$Team=0;$OrderByInput=array();$TeamStatPossibleOrderField=array();$HistoryFarm=False;$lang="en";$DESCQuery=False;}?> 
<form id="SearchHistoryTeams" action="ProTeam.php" method="get">
<table class="STHSTable">
<tr>	
	<td class="STHSW200 STHSPHPSearch_Field"><?php echo $SearchLang['Year'];?></td><td class="STHSW250">
	<select name="Year" class="STHSSelect STHSW250">
	<?php 
	echo "<option";if($Year == 0){echo " selected=\"selected\"";} echo " value=\"\">" .  $SearchLang['ThisSeason'] . "</option>";
	if (empty($HistoryYear) == false){while ($Row = $HistoryYear ->fetchArray()) { 
		echo "<option";if($Row['Year'] == $Year){echo " selected=\"selected\"";} echo " value=\"" . $Row['Year'] . "\">" . $Row['Year'] . "</option>"; 
	}}
	?>
	</select></td>	
</tr>
<tr>
	<td class="STHSW200 STHSPHPSearch_Field"><?php echo $SearchLang['Team'];?></td><td class="STHSW250">
	<select name="Team" class="STHSSelect STHSW250">
	<?php
	if (empty($HistoryTeam) == false){while ($Row = $HistoryTeam->fetchArray()) {
		echo "<option value=\"" . $Row['Number'] . "\""; 
		if ($Row['Number'] == $Team){echo " selected=\"selected\"";}
		echo ">" . $Row['Name'] . "</option>"; 
	}}
	?>
	</select></td>
</tr>
<tr>
	<td class="STHSW200 STHSPHPSearch_Field"><?php echo $SearchLang['Playoff'];?></td><td class="STHSW250">
	<input type="checkbox" name="Playoff"<?php if($Playoff == "True"){echo " checked";}?>></td>
</tr>
<?php If ($HistoryFarm == True){echo "<tr><td class=\"STHSW200\">" . $SearchLang['Farm'] . "</td><td class=\"STHSW250\"><input type=\"checkbox\" id=\"SearchHistoryTeamsFarm\" name=\"Farm\"";if($TypeText == "Farm"){echo " checked";}echo "></td></tr>";}?>
<tr>
	<td colspan="2" class="STHSCenter"><input type="submit" class="SubmitButton" value="<?php echo $SearchLang['Submit'];?>"></td>
</tr>
</table></form>
<script>
document.getElementById("SearchHistoryTeams").addEventListener("submit", function (e) {
    const farmChecked = document.getElementById("SearchHistoryTeamsFarm").checked;
    if (farmChecked) {
        this.action = "FarmTeam.php";
    } else {
        this.action = "ProTeam.php";
    }
});
</script>




