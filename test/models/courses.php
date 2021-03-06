<?php
class course
{
	# class reflects a db table
	public static $tableName = "TABLE1";
	public static $errors = [];

	# data columns
	public $CourseName;
	public $Subject;
	public $CourseNumber;
	public $Section;
	public $Title;
	public $CRN;
	public $ScheduleType;
	public $Instructor;
	public $Days;
	public $Start;
	public $End;
	public $Bldg;
	public $Room;
	

	function copyFromRow( $row ) {
		$this->CourseName = $row['Course'];
		$this->Subject = $row['Subject'];
		$this->CourseNumber = $row['CourseNumber'];
		$this->Section = $row['Section'];
		$this->Title = $row['Title'];
		$this->CRN = $row['CRN'];
		$this->ScheduleType = $row['ScheduleType'];
		$this->Instructor = $row['Instructor'];
		$this->Days = $row['Days'];
		$this->Start = $row['Start'];
		$this->End = $row['End'];
		$this->Bldg = $row['Bldg'];
		$this->Room = $row['Room'];
	}

	/* function __toString() {
		return $this->name." ".$this->pretime." ".$this->totaltime." ".$this->rating." ".$this->id;
	} */

	# inflation - making this object match a db row
	function findByCourseName( $CourseName, $dbh ) {
		$stmt = $dbh->prepare( "select * from ".course::$tableName." where CourseName = :CourseName" );
		$stmt->bindParam( ':CourseName', $CourseName );
		$stmt->execute();

		$course = new course();
		$row = $stmt->fetch();
		$course->copyFromRow( $row );
		return $course;
	}

	# Return all courses
	static function findAll( $dbh ) {
		$stmt = $dbh->prepare( "select * from ".course::$tableName );
		$stmt->execute();

		$result = array();
		while( $row = $stmt->fetch() ) {
			$course = new course();
			$course->copyFromRow( $row );
			$result[] = $course;
		}
		return $result;
	}

	# Return all courses of a certain subject
	static function findCoursesBySubject($subject, $dbh)
	{
		$stmt = $dbh->prepare( "select * from ".course::$tableName." where Subject = :Subject" );
		$stmt->bindParam( ':Subject', $subject );
		$stmt->execute();

		while( $row = $stmt->fetch() ) {
			$course = new course();
			$course->copyFromRow( $row );
			$result[] = $course;
		}
		return $result;
	}

	# Return all unique courses of a certain subject. e.g. Only return CSCI 1301.01 and not CSCI 1301.01 and CSCI 1301.02 
	static function findDistinctCoursesBySubject($subject, $dbh)
	{
		$stmt = $dbh->prepare( "SELECT DISTINCT CourseNumber,Title FROM ".course::$tableName." WHERE Subject = :Subject" );
		$stmt->bindParam( ':Subject', $subject );
		$stmt->execute();

		while( $row = $stmt->fetch() ) {
			$course = new course();
			$course->CourseNumber = $row['CourseNumber'];
			$course->Title = $row['Title'];
			$result[] = $course;
		}
		return $result;
	}
	
	
	static function getAllSections($courses, $dbh)
	{
		//grabs all sections of the chosen classes
		foreach ($courses as $c)
		{
			$stmt = "";
			
			if ($c["Online"] === "yes") {
			  	$stmt = $dbh->prepare( "SELECT * FROM ".course::$tableName." WHERE Subject = :Subject AND CourseNumber = :CourseNumber AND Section LIKE '%L'" );
			} else if ($c["Online"] === "no") {	
				$stmt = $dbh->prepare( "SELECT * FROM ".course::$tableName." WHERE Subject = :Subject AND CourseNumber = :CourseNumber AND Days != '' AND Start != '0' AND End != '0'" );
			} else {
				$stmt = $dbh->prepare( "SELECT * FROM ".course::$tableName." WHERE Subject = :Subject AND CourseNumber = :CourseNumber" );
			}
			
			$stmt->bindParam( ':Subject', $c["Subject"] );
			$stmt->bindParam( ':CourseNumber', $c["CourseNumber"] );

			$stmt->execute();
			
			while( $row = $stmt->fetch() ) 
			{
				$course = new course();
				$course->copyFromRow($row);
				$result[] = $course;
			}

			if ($c["Online"] === "yes") {
				if(sizeof($result) === 0) {
					course::$errors[] = "No online courses available for " . $c["Subject"] . " " . $c["CourseNumber"];
				}
			}
		}
		return $result;
	}
	
	
	static function chooseASection($schedule)
	{
		//chooses the first section in the list of all classes
		$course = new course();
		$counter = 1;
		foreach($schedule as $s)
		{
			if(counter == 1)
			{
				$course = $s;
				$result[] = $course;
			}
			else
			{
				if($course->Title != $s->Title)
				{
					$course = $s;
					$result[] = $course;
				}
			}
			$counter = $counter + 1;
			
		}
		echo $result;
		return $result;
	}
	
	static function returnOnlineClasses($schedule)
	{
		//searches through all classes in schedule and removes all classes that arent online
		$course = new course();
		$searchPAram = 'L';
		foreach($schedule as $s)
		{
			if (strpos($s->Section, $searchParam) !== FALSE)
			{
				$course = $s;
				$result[] = $course;
			}
		}
		
		return $result;
	}
	
	static function removeOnlineClasses($schedule)
	{
		//searches through all classes in schedule and removes all classes that are online
		$course = new course();
		$searchPAram = 'L';
		foreach($schedule as $s)
		{
			if (strpos($s->Section, $searchParam) === FALSE)
			{
				$course = $s;
				$result[] = $course;
			}
		}
		
		return $result;
	}
	
	static function removeCoursesByDay($schedule, $dayOff)
	{
		//searches through all classes in schedule to remove specific days, can be easily modded to remove any day
		$course = new course();
		foreach($schedule as $s)
		{
			if (strpos($s->Days, $dayOff) === FALSE)
			{
				$course = $s;
				$result[] = $course;
			}
		}
		return $result;
	}
	
	static function removeCoursesByTime($schedule, $start, $end)
	{
		//searches through all classes in schedule to remove specific classes by start and end time
		//if classes fall outside boundaries of preference, they are removed
		$course = new course();
		
		foreach($schedule as $s)
		{
			if ((int)$s->Start >= $start && (int)$s->End <= $end)
			{
				$course = $s;
				$result[] = $course;
			}
		}
		return $result;
	}
	
	static function removeCoursesByDayAndTime($schedule, $day, $start, $end)
	{
		//searches through all classes in schedule to remove specific classes by start and end time on specific day
		//if classes fall outside boundaries of preference, they are removed
		$course = new course();

		foreach($schedule as $s)
		{
			if (strpos($s->Days, $day) !== FALSE)
			{
				if ((int)$s->Start >= (int)$start && (int)$s->End <= (int)$end)
				{
					$course = $s;
					$result[] = $course;
				}
			}
			else
			{
				$course = $s;
				$result[] = $course;
			}
		}
		return $result;
		
	}
	
	static function createValidSchedule($schedule)
	{
	//makes sure classes start and end times dont overlap
	//works with any amount of classes
		$course = new course();
		$counter = 0;
		foreach($schedule as $s)
		{
			if($counter == 0)
			{
			//adds first classes first section no matter what
				$course = $s;
				$result[] = $course;
				$counter = $counter + 1;
			}
			else
			{
				if($course->Title != $s->Title)
				{
					for($i = 0; $i < $counter; $i++)
					{
					//calls the timeoverlap function to check for overlapping
						if(course::timeOverlap($s, $result[$i]))
						{
							$course = $s;
							$result[] = $course;
							$counter++;
						}

					}
				}
			}
		}
		return $result;
	}
	
	static function timeOverlap($first, $second)
	{
	//checks to see if the times for two classes overlap
		if(!($first->Start >= $second->Start && $first->Start <= $second->End) && !($first->End >= $second->Start && $first->End <= $second->End))
		{
		//checks the times and returns true if they dont
			return true;
		}
		else if(!($first->Start >= $second->Start && $first->Start <= $second->End) && !($first->End >= $second->Start && $first->End <= $second->End) && $first->Days !=$second->Days)
		{
		//checks the days and returns true if they dont
			return true;
		}
		else
		{
		//else they do
			return false;
		}
	}
	
	static function makeArray($schedule)
	{
	//makes a 2 dimensional array with all the classes that are passed to it
		$course = new course();
		$titleCounter = 0;
		$sectionCounter = 0;
		$counter = 0;
		foreach($schedule as $s)
		{
			if($counter == 0)
			{
				$course = $s;
				$counter++;
			}
			//if($course->Title != $s->Title) // Does not work, because POLS 2313 and POLS 2314 have the same title but are different courses
			if($course->Subject.$course->CourseNumber != $s->Subject.$s->CourseNumber)
			{
				$titleCourse = $s;
				$result[$titleCounter] = $array;
				$sectionCounter = 0;
				unset($array);
				$array = array();
				$array[$sectionCounter] = $course;
				$titleCounter++;
			}
			
			$course = $s;
			$array[$sectionCounter] = $course;
			$sectionCounter++;
			
			
		}
		$result[$titleCounter] = $array;
		return $result;
		
	}

	# The following code is by barfoon from StackOverflow
	# http://stackoverflow.com/a/8567479
	#$arrays contains all possible courses a person can take.
	#Each array in $arrays is organized by course
	#For example $arrays[0] contains an array of all possible ENG 1301 courses
	#For example $arrays[1] contains an array of all possible CSCI 1370 courses
	#And so on...
	#This function will return all possible combinations of all those courses
	static function createAllPossibleSchedules($arrays)
	{
	    $result = array();
	    $arrays = array_values($arrays);

	    $size = sizeof($arrays) > 0 ? 1 : 0;

	    #Calculate the number of combinations
	    foreach ($arrays as $array)
	    {
	        $size *= sizeof($array);
	    }

	    $scheduleLimit = 30000;
	    if($size > $scheduleLimit) {
	    	course::$errors[] = "Too many schedules generated, please input some preferences";
	    	return $result;
	    }

	    #Make each schedule
	    for ($i = 0; $i < $size; $i++)
	    {
	        $result[$i] = array();

	        #The size of $arrays is equal to the number of courses that a person is taking
	        for ($j = 0; $j < sizeof($arrays); $j++)
	        {
	        	#Put next course in the array
	        	$currentCourse = current($arrays[$j]);
	            $result[$i][] = $currentCourse;
	        }

	        for ($j = (sizeof($arrays) -1); $j >= 0; $j--)
	        {
	        	#Advance to next course for the next subject
	        	#If there is another course, break out of the loop, this way only one course is changed per schedule
	            if (next($arrays[$j]))
	            {
	                break;
	            }

	            #If there is not another course and $arrays[$j] is set:
	            #reset the pointer to the first element
	            elseif (isset ($arrays[$j]))
	            {
	                reset($arrays[$j]);
	            }
	        }
	    }
	    return $result;
	}

	static function removeOverlappingCourses($schedules)
	{
		foreach($schedules as $s)
		{
			$courseOverlap = false;
			foreach ($s as $course1)
			{
				foreach ($s as $course2)
				{
					if ($course1 !== $course2)
					{
						//if($course1->Days === $course2->Days || strpos($course1->Days, $course2->Days) !== FALSE ||strpos($course2->Days, $course1->Days) !== FALSE)
						if(course::daysOverLap($course1, $course2))
						{
							if (strpos($course1->Section, 'L') === FALSE)
							{
								if(!course::timeOverlap($course1,$course2))
								{
									$courseOverlap = true;
								}	
							}	
						}
					}
				}
			}

			if($courseOverlap === false)
				$result[] = $s;

		}
		return $result;
	}

	static function daysOverLap($course1, $course2)
	{
		if ($course1->Days === "" || $course2->Days === "")
			return FALSE;
		if ($course1->Days === $course2->Days)
			return TRUE;

		// //New method
		// $course1Days = explode(" ", $course1->Days);

		// //Check if any character in $course1->Days exists in $course2->Days
		// foreach($course1Days as $course1Day) {
		// 	if (strpos($course2->Days, $course1Day) !== FALSE) {
		// 		return TRUE;
		// 	}
		// }


		//old method
		if(strpos($course1->Days, $course2->Days) !== FALSE)
			return TRUE;
		if(strpos($course2->Days, $course1->Days) !== FALSE)
			return TRUE;
		return FALSE;	
	}

	static function removeNonBlockSchedules($schedules)
	{
		foreach($schedules as $s)
		{
			if(course::isBlockSchedule($s)) {
				$result[] = $s;
			}
			//echo "<br><br><br>";
		}
		return $result;
	}

	static function removeBlockSchedules($schedules)
	{
		foreach($schedules as $s)
		{
			if(!course::isBlockSchedule($s)) {
				$result[] = $s;
			}
			//echo "<br><br><br>";
		}
		return $result;
	}

	static function isBlockSchedule($schedule)
	{
		//Sort the schedule by start time

		usort($schedule, "course::sortByTime");

		// foreach($schedule as $c) {
		// 	echo $c->Subject . " " . $c->CourseNumber . " " . $c->Start . "<br>";
		// }


		$days = ["M","T","W","R","F"];

		// Go through each day
		foreach($days as $day) {
			//echo "<br>" . $day . "<br>";

			$tempArray = []; //Used to hold courses that are in $day

			foreach($schedule as $c) {	
				if( strpos($c->Days, $day) !== FALSE ) {
					$tempArray[] = $c;
					//echo $c->Subject . " " . $c->CourseNumber . " " . $c->Start . "<br>";
				}
			}

			for($i = 0; $i < sizeof($tempArray)-1; $i++) {
				if($day === "T" || $day === "R") { //Check for lunch break. 12:00 pm - 1:00 PM
					if( $tempArray[$i]->End == 1150 && $tempArray[$i+1]->Start == 1310 || $tempArray[$i]->End == 1150 && $tempArray[$i+1]->Start == 1300) {
						// Lunch break, ignore
						continue;
					}
				}
				if( ($tempArray[$i+1]->Start - $tempArray[$i]->End) - 40 > 45 ) { //Theres a gap bigger than 45 minutes, so its not a block schedule
					return FALSE;
				}
			}
		}
		return TRUE;

		// //Check to see if its a block schedule
		// //If it is //(Right now it always returns trues)
		// return TRUE;
		// //else
		// return FALSE;
	}

	static function getErrors()
	{
		return course::$errors;
	}

	static function sortByTime($a, $b)
	{
		if ($a == $b) {
        	return 0;
    	}
    	return ($a->Start < $b->Start) ? -1 : 1;
    	//return ($a->Start < $b->Start) ? 1 : -1;
	}
}
?>