<?php
/*
This file is part of VCMS.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
*/

if(!is_object($libGlobal) || !$libAuth->isLoggedin())
	exit();


class LibBirtdayTimelineEvent extends LibTimelineEvent{
	function getBadgeClass(){
		return 'birthday';
	}

	function getBadgeIcon(){
		return '<i class="fa fa-birthday-cake" aria-hidden="true"></i>';
	}
}


$dateTimeStart = new DateTime($zeitraum[0]);
$dateTimeEnd = new DateTime($zeitraum[1]);

$period = new DatePeriod($dateTimeStart, new DateInterval('P1Y'), $dateTimeEnd);
$years = array();

foreach($period as $date){
    $years[] = $date->format('Y');
}

foreach($years as $year){
	addBirthdayTimelineEvents($year, $zeitraum);
}

function addBirthdayTimelineEvents($year, $zeitraum){
	global $libDb, $libTime;

	$stmt = $libDb->prepare("SELECT id, datum_geburtstag FROM base_person WHERE (gruppe='P' OR gruppe='B' OR gruppe='F') AND datum_geburtstag != '' AND datum_geburtstag != '0000-00-00'");
	$stmt->execute();

	while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
		$birtdayYear = $libTime->formatYearString($row['datum_geburtstag']);
		$age = $libTime->checkSignificantBirthdayYear($birtdayYear, $year);

		if($age){
			$dateObject = new DateTime($row['datum_geburtstag']);
			$dateObject->add(new DateInterval('P' .$age. 'Y'));
			$date = $dateObject->format('Y-m-d');

			if($zeitraum[0] <= $date && $date <= $zeitraum[1] && $date <= date('Y-m-d')){
				addBirthdayTimelineEvent($row, $date, $age);
			}
		}
	}
}

function addBirthdayTimelineEvent($row, $date, $age){
	global $libMitglied, $timelineEventSet;

	$title = 'Geburtstag von ' .$libMitglied->getMitgliedNameString($row['id'], 0);
	$description = $age. ' Jahre';
	$url = 'index.php?pid=intranet_person_daten&amp;personid=' .$row['id'];

	$timelineEvent = new LibBirtdayTimelineEvent();

	$timelineEvent->setTitle($title);
	$timelineEvent->setDatetime($date);
	$timelineEvent->setDescription($description);
	$timelineEvent->setReferencedPersonId($row['id']);
	$timelineEvent->setUrl($url);

	$timelineEvent->hideReferencedPersonSignature();

	$timelineEventSet->addEvent($timelineEvent);
}
?>