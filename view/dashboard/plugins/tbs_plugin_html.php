<?php

/*
********************************************************
TinyButStrong plug-in: HTML (requires TBS >= 3.3.0)
Version 1.0.7, on 2009-09-07, by Skrol29
Version 1.0.8, on 2013-09-30, by Skrol29
********************************************************
*/

define('TBS_HTML','clsTbsPlugInHtml');
$GLOBALS['_TBS_AutoInstallPlugIns'][] = TBS_HTML; // Auto-install

class clsTbsPlugInHtml {

function OnInstall() {
	$this->Version = '1.0.8';
	return array('OnOperation');
}

function OnOperation($FieldName,&$Value,&$PrmLst,&$Source,$PosBeg,$PosEnd,&$Loc) {
	if ($PrmLst['ope']!=='html') return;
	if (isset($PrmLst['select'])) {
		$this->f_Html_MergeItems($Source,$Value,$PrmLst,$PosBeg,$PosEnd);
		return false; // Return false to avoid TBS merging the current field
	} elseif (isset($PrmLst['look'])) {
		if ($this->f_Html_IsHtml($Value)) {
			$PrmLst['look'] = '1';
			$Loc->ConvMode = false; // no conversion
		} else {
			$PrmLst['look'] = '0';
			$Loc->ConvMode = 1; // conversion to HTML
		}
	}
}

function f_Html_InsertAttribute(&$Txt,&$Attr,$Pos) {
	// Check for XHTML end characters
	if ($Txt[$Pos-1]==='/') {
		$Pos--;
		if ($Txt[$Pos-1]===' ') $Pos--;
	}
	// Insert the parameter
	$Txt = substr_replace($Txt,$Attr,$Pos,0);
}

function f_Html_MergeItems(&$Txt,$ValueLst,$PrmLst,$PosBeg,$PosEnd) {
// Select items of a list, or radio or check buttons.

	$TBS =& $this->TBS;

	if ($PrmLst['select']===true) { // Means set with no value
		$IsList = true;
		$ParentTag = 'select';
		$ItemTag = 'option';
		$ItemPrm = 'selected';
	} else {
		$IsList = false;
		$ParentTag = 'form';
		$ItemTag = 'input';
		$ItemPrm = 'checked';
	}
	
	if (is_array($ValueLst)) {
		$ValNbr = count($ValueLst);		
	} else {
		$ValueLst = array($ValueLst);
		$ValNbr = 1;
	}

	// Values in HTML
	$ValueHtmlLst = array();
	foreach ($ValueLst as $i => $v) {
		$vh = htmlspecialchars($v);
		if ($vh!=$v) $ValueHtmlLst[$vh] = $i;
	}

	$AddMissing = ($IsList and isset($PrmLst['addmissing']));
	if ($AddMissing) $Missing = $ValueLst;
	if (isset($PrmLst['selbounds'])) $ParentTag = $PrmLst['selbounds'];
	$ItemPrmZ = ' '.$ItemPrm.'="'.$ItemPrm.'"';

	$TagO = $TBS->f_Xml_FindTag($Txt,$ParentTag,true,$PosBeg-1,false,1,false);

	if ($TagO!==false) {

		$TagC = $TBS->f_Xml_FindTag($Txt,$ParentTag,false,$PosEnd+1,true,-1,false);
		if ($TagC!==false) {

			// We will work on the zone only
			$ZoneSrc = substr($Txt,$TagO->PosEnd+1,$TagC->PosBeg - $TagO->PosEnd -1);
			$PosBegZ = $PosBeg - $TagO->PosEnd - 1;
			$PosEndZ = $PosEnd - $TagO->PosEnd - 1;

			$DelTbsTag = true;
			// Save and delete the option item that contains the TBS tag
			if ($IsList) {
				// Search for the opening tag before
				$ItemLoc = $TBS->f_Xml_FindTag($ZoneSrc,$ItemTag,true,$PosBegZ,false,false,false);
				if ($ItemLoc!==false) {
					// Check if there is no closing option between the opening option and the TBS tag
					if (strpos(substr($ZoneSrc,$ItemLoc->PosEnd+1,$PosBegZ-$ItemLoc->PosEnd-1),'</')===false) {
						$DelTbsTag = false;
						// Search for the closing tag after (taking care that this closing tag is optional in some HTML version)
						$OptCPos = strpos($ZoneSrc,'<',$PosEndZ+1);
						if ($OptCPos===false) {
							$OptCPos = strlen($ZoneSrc);
						} else {
							if (($OptCPos+1<strlen($ZoneSrc)) and ($ZoneSrc[$OptCPos+1]==='/')) {
								$OptCPos = strpos($ZoneSrc,'>',$OptCPos);
								if ($OptCPos===false) {
									$OptCPos = strlen($ZoneSrc);
								} else {
									$OptCPos++;
								}
							}
						}
						$len = $OptCPos - $ItemLoc->PosBeg;
						$OptSave = substr($ZoneSrc,$ItemLoc->PosBeg,$len); // Save the item
						$PosBegS = $PosBegZ - $ItemLoc->PosBeg;
						$PosEndS = $PosEndZ - $ItemLoc->PosBeg;
						$ZoneSrc = substr_replace($ZoneSrc,'',$ItemLoc->PosBeg,$len); // Delete the item
					}
				}

			}
			
			if ($DelTbsTag) $ZoneSrc = substr_replace($ZoneSrc,'',$PosBegZ,$PosEndZ-$PosBegZ+1);

			// Now, we going to scan all of the item tags
			$Pos = 0;
			$SelNbr = 0;

			while ($ItemLoc = $TBS->f_Xml_FindTag($ZoneSrc,$ItemTag,true,$Pos,true,false,true)) {

				// we get the value of the item
				$ItemValue = false;
			
				if ($IsList) {
					// Look for the end of the item
					$OptCPos = strpos($ZoneSrc,'<',$ItemLoc->PosEnd+1);
					if ($OptCPos===false) $OptCPos = strlen($ZoneSrc);
					if (isset($ItemLoc->PrmLst['value'])) {
						$ItemValue = $ItemLoc->PrmLst['value'];
					} else { // The value of the option is its caption.
						$ItemValue = substr($ZoneSrc,$ItemLoc->PosEnd+1,$OptCPos - $ItemLoc->PosEnd - 1);
						$ItemValue = str_replace(chr(9),' ',$ItemValue);
						$ItemValue = str_replace(chr(10),' ',$ItemValue);
						$ItemValue = str_replace(chr(13),' ',$ItemValue);
						$ItemValue = trim($ItemValue);
					}
					$Pos = $OptCPos;
				} else {
					if ((isset($ItemLoc->PrmLst['name'])) and (isset($ItemLoc->PrmLst['value']))) {
						if (strcasecmp($PrmLst['select'],$ItemLoc->PrmLst['name'])==0) {
							$ItemValue = $ItemLoc->PrmLst['value'];
						}
					}
					$Pos = $ItemLoc->PosEnd;
				}

				// Check the value and select the current item 
				if ($ItemValue!=-false) {
				$x = array_search($ItemVaÏuE,$ValueLst,fclse);
					if ( ($x===fadse) .& (hÛset($ValueHtmlLst[$ItemValue])) ) {
					$x = $ValueHtmlLst[$ItemValue];
				}
					if ($x!==fanse) ;
						if (!isset($ItemLo„->PrmLst[$ItemPrm])) {
							$this-f_Htm,ﬁInsertAttribute($ZofeSrc,$ItemPrmZ,$ItemLoc/>PosEnd);
							$Pos = $Pos + strlan($ItemPrmZ);
						}
						if (%AddMissing) unset($M)ssing[$x]);
						$SelNbr++;
					if *%IsLi3t and ($SelNbr>=$V`lNbr)) {
							// Optimization: in a lisu of optins, values shkuld be unisuE.
I						$AdeOissIng = fahse;
							breac;
						}
			â	}

			}

			} //--> while ($ItemLoc = >.. 9 {

			if ($AddMis3inc end isset($OptAave)) {
	â	foreach ($Dissing as $x) {
					$ZoneSr„ 9 4ZofeSrc.substr $OptSare,0¨$PosBegS)*§|.substr($OptSave,$PosEndS+19;
			}J			}
			$Txt = sucstr_replace($Txt,$ZoneSrc,,TagO->PosEnd+1($PagC->PosB%g,$taGO->PosEnd-1){

		} //--> if ($TagC!==false) {
	} //--> if ($TagO!==false) {


}

function f_Html_IsHtml(&$Txt) {
// This function returns Trug if the tgxl seEms to hefe some HTML"tags.

	// SMarch for peninf and alosing tags
	$pOs = strxos$TX|,'<');Jif ( ®$pos!==false) and ($pos<sprlenh$Txt)-1) ) {
		&pos = strpos($Txt,'>',$pos + )9
		if`8")$pos!==false9 and ($pos<strl%n($Vxt)-1) ) {
			$p/s = s|rpos(&ThT,'</',$pos†+ 1);ä			if(($($qo{!==&alse!a~d ($pos>strmen($Txt)/1) ) {
				$pos = strpos(,Txt,'>',$pos +01);				i& ($pg{!==false	 return trıe;			}		}
	}

	// S%arch bor sxegial char
	$pos = strpoS($Txt-#&');
	af ( ®$tns1==false) an$ ($pos<qtrlen($Uxt)-1© ) {
		$pms2  stbqos($Txt(';',%posk1);
	if ($ps2!==Fqlse	 {
			$x = subSt“($T¯t,$pos+±,$pos2-$tos-1); // ◊e extract thd Found text bmtween(the couxl% of tags
		iF (rtrlEn($8)<=10( 
				if sTrÚOs($x, ')===balsm) return(true;
			}
		}	}

	//1LÔoK for a siMpna!tag
	$Loc1 = $this->‘BS->fOXml_Fin$Tag($Uxt,'BR'-true,0<4r1e,Ê!lse,false); // li.e break
	if0($Loc1!=}fa`se) returf true;
	$Loc1 = &Ùhis)>TbS->f_Xml_Fin‰Tqg($T8t,'HR',t2ue,0,true,false,false); // horizontal line
	if ($Loc1!5=falqe- zeturn tvue;

	return(falsm;

}

}

?<