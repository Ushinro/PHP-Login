<?php
function checkBrute($userId, $connection) {
	define('TIME_INTERVAL',      2);  // In hours
	define('SECONDS_PER_MINUTE', 60);
	define('MINUTES_PER_HOUR',   60);

	$failedLoginLimit = 5;
	$now = time();

	// All login attempts are counted from the past 2 hours. 
	$valid_attempts = $now - (TIME_INTERVAL * SECONDS_PER_MINUTE * MINUTES_PER_HOUR);
 
	if ($stmt = $connection->prepare("SELECT * 
					FROM `login_attempts`
					WHERE `user_id` = :id
						AND `time` > '$valid_attempts'
						AND `successful` = 0;"
					)) {
		$stmt->bindParam(':id', $userId);
	
		$stmt->execute();
		$rows = $stmt->fetchAll();
	
		// If there have been more than 5 failed logins 
		if (count($rows) > $failedLoginLimit) {
			return true;
		} else {
			return false;
		}
	}
}


function dayToDrexelDay($day) {
	switch($day) {
		case 'Monday':
		case 'Mon':
			return 'M';
			break;
		case 'Tuesday':
		case 'Tue':
			return 'T';
			break;
		case 'Wednesday':
		case 'Wed':
			return 'W';
			break;
		case 'Thursday':
		case 'Thu':
			return 'R';
			break;
		case 'Friday':
		case 'Fri':
			return 'F';
			break;
	}
}


function to12HourFormat($time) {
	return date('h:i A', $time);
}


function prefixSectionNumber($section) {
	if ($section < 10) {
		return '00' . $section;
	} else if ($section < 100) {
		return '0' . $section;
	} else {
		return $section;
	}
}


function flattenArray(array $items) {
	$flattened = iterator_to_array(new RecursiveIteratorIterator(
		new RecursiveArrayIterator($items)
	), false);

	return $flattened;
}


function storeExcelData($targetFile, $classId) {
	// Include the Excel library if a file is uploaded
	require_once 'vendor/PHPExcel/PHPExcel.php';

	$db = Database::getInstance();

	$id = $lastName = $firstName = $middleName = $levelName = $classification = $majorName = $email = null;
	
	try {
		// die(var_dump(PHPExcel_IOFactory::load($targetFile)));
		$objPHPExcel = PHPExcel_IOFactory::load($targetFile);

		// Loop to get all sheets in a file.
		for($sheet = 0, $numSheets = $objPHPExcel->getSheetCount(); $sheet < $numSheets; $sheet++) {    
			$objWorksheet = $objPHPExcel->setActiveSheetIndex($sheet);

			// Checking sheet not empty
			if(!empty($objWorksheet)) {
				$classList = '';

				// Loop used to get each row of the sheet
				// Skip the column title by starting the row at 2
				for($row = 2, $numRows = $objWorksheet->getHighestRow(); $row <= $numRows; $row++) {
					$studentId      = filter_var($objWorksheet->getCellByColumnAndRow(0, $row)->getValue(), FILTER_SANITIZE_NUMBER_INT);
					$lastName       = filter_var($objWorksheet->getCellByColumnAndRow(1, $row)->getValue(), FILTER_SANITIZE_STRING);
					$firstName      = filter_var($objWorksheet->getCellByColumnAndRow(2, $row)->getValue(), FILTER_SANITIZE_STRING);
					$middleName     = filter_var($objWorksheet->getCellByColumnAndRow(3, $row)->getValue(), FILTER_SANITIZE_STRING);
					$levelName      = filter_var($objWorksheet->getCellByColumnAndRow(4, $row)->getValue(), FILTER_SANITIZE_STRING);
					$classification = filter_var($objWorksheet->getCellByColumnAndRow(5, $row)->getValue(), FILTER_SANITIZE_STRING);
					$majorName      = filter_var($objWorksheet->getCellByColumnAndRow(6, $row)->getValue(), FILTER_SANITIZE_STRING);
					$email          = filter_var($objWorksheet->getCellByColumnAndRow(7, $row)->getValue(), FILTER_SANITIZE_EMAIL);

					$studentExists = $db->get('students', ['student_id', '=', $studentId])->count();

					// Check if student already exists,
					// if not, add the student,
					// otherwise update their information.
					if (!$studentExists) {
						$db->insert('students', [
							'student_id'     => $studentId,
							'last_name'      => $lastName,
							'first_name'     => $firstName,
							'middle_name'    => $middleName,
							'level_name'     => $levelName,
							'classification' => $classification,
							'major_name'     => $majorName,
							'email'          => $email
						]);
					} else {
						$student = $db->get('students', ['student_id', '=', $studentId])->first();
						
						$db->update('students', $student->id, [
							'level_name'     => $levelName,
							'classification' => $classification,
							'major_name'     => $majorName
						]);
					}

					// Separate ids with ','
					// and make them accessible with explode() call.
					$student = $db->get('students', ['student_id', '=', $studentId])->first();
					$classList .= $student->id . ",";
				}
				$classList = rtrim($classList, ',');

				$db->update('classes', $classId, [
					'student_ids' => $classList
				]);
			}
		}
	} catch(PHPExcel_Reader_Exception $e) {
		echo 'Error loading file: ' . $e->getMessage();
	}
}

function isFileValid($file, $tmpFile = '') {
	if (trim(empty($tmpFile))) {
		// AJAX
		$tmpFile = $file;
	}
	$targetFile    = basename($file);
	$fileType      = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
	$fileSize      = filesize($tmpFile);
	$fileSizeLimit = 5 * 1024 * 1024;       // 5 MB
	
	// Limit file size.
	if ($fileSize > $fileSizeLimit) {
		echo 'Error: File size exceeds limit. ';
		return false;
	}
	
	// Limit file type to .xls.
	if ($fileType === 'xls') {
		// Open file to check for a specific line
		$xml = file_get_contents($tmpFile);
		$string = '<?mso-application progid="Excel.Sheet"?>';
		if (strpos($xml, $string) === false) {
			echo 'Error: File is invalid. ';

			return false;
		}
	} else if ($fileType !== 'xlsx') {
		// Valid file type, skip
	} else {
		echo 'Error: File is invalid. ';

		return false;
	}

	return true;
}

function removeFile($file) {
	unlink($file);
}