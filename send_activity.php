<?php
require_once('PHPMailer/PHPMailerAutoload.php');
header("Content-type: text/html; charset=utf-8");

if (!isset($_POST['submit'])) {
	die('Invalid access');
}

//��POST��
$filename = $_POST['filename'];
$path = $_POST['path'];
$email = $_POST['email'];
$testonly = isset($_POST['testonly']);

// �ˬd Path Traversal ����
if (strstr($filename, "..") ||
	strstr($path, "..")) {
	die('Access Denied');
}

$delimiter = "\n";
$rootpath = "/WWW/epaper/";
$url = "//www.mkt.org.tw/";
$contains = '�p�ݤ��쥻�ʹq�l���ο򥢹��ɡA<a href="'.$url."epaper/".$path.$filename.'">�i�Ы����j</a><br/>
			�p�n�����q�\�q�l���A<a href="https://www.mkt.org.tw/cancelepaper">�i�Ы����j</a><br/><br/>';

$mail = new PHPMailer();	// �إ߷s����		
//$mail->SetLanguage("zh", 'PHPMailer/language');

//$mail->IsSendmail();						   
$mail->IsSMTP();					// �]�w�ϥ�SMTP�覡�H�H
$mail->Host = "localhost";				// �ϥ� Local SMTP Server
$mail->From = "service@yingting.org.tw";		// �]�w�H��̫H�c		

$mail->CharSet = "UTF-8";				// �]�w�l��s�X   
$mail->Encoding = "base64";
$mail->WordWrap = 50;					// �C50�Ӧr���۰��_��
	
$mail->FromName = "YingTing";				// �]�w�H��̩m�W		
$mail->Subject = "�i�q���jHello World�I";
	
$mail->IsHTML(true);					// �]�w�l�󤺮e��HTML 

//����Ʈwemail
$host = "localhost";
$username = "";
$password = "";
$database = "";
	
$mailer = array();
	
$con = mysqli_connect($host, $username, $password, $database);

session_start();
if(mysqli_connect_errno($con))
{
	echo "Fail to connect to MySQL: ".mysqli_connect_error();
}
else
{
	// ********************** ��ܸ�Ʈw ******************************
	$sql = "SELECT * FROM email_list WHERE will = '1'";
	$result = mysqli_query($con, $sql);
	
	$num = 0; 
	while($row = mysqli_fetch_array($result)) {
		$mailer[$num]['email'] = $row['email'];
		$mailer[$num]['no'] = $row['no'];
		$mailer[$num]['name'] = $row['name'];
		$num++;
	}
}

// ���N email content �ǳƦn
/*** �]�w counter ��l�� ***/
$i = 1;

/*** �}�� ***/
$fp = fopen($rootpath.$path.$filename, 'r');

$fp2 = fopen($rootpath.'test.txt', 'w');

/*** ���_ loop pointer ***/
while ( !feof ( $fp) )
{
	/*** �N���Ū�J��y�� ***/
	$buffer = stream_get_line( $fp, 1024, $delimiter );
	$t = 'src="'.$url.$path;
	//��src��������|
	if (strpos ($buffer, 'src="http://')){
		 //nothing to do
		 //echo "$buffer";
	} else if (strpos ($buffer, 'src="//')){
	} else if (strpos ($buffer, 'src="https://')){
	} else {
		//echo "���Nsrc";
		 //��src��������|
		 $buffer = str_replace('src="',$t,$buffer);
		 //echo "��src��������| $buffer";
	}

	$t2 = 'href="'.$url.$path;
	//��href��������|
	if (strpos ($buffer, 'href="http') || strpos ($buffer, 'href="mailto')){
		 //nothing to do
		 //echo "$buffer";
	} else {
		 //��href��������|
		 $buffer = str_replace('href="',$t2,$buffer);
		 //echo "��href��������| $buffer";
	}
	
	/*** �L�X�Ӧ檺�� ***/
	echo "$buffer";
	
	$contains .= "$buffer";
	fwrite($fp2, $buffer);


	/*** ���W counter ***/
	$i++;
	
	/*** �M���O���� ***/
	$buffer = '';
}

fclose($fp);
fclose($fp2);

$mail->Body = $contains;
$mail->AltBody="This is text only alternative body.";	

if ($email != null) {
	$test_mails = preg_split("/[,; \n]/", $email);
	foreach ($test_mails as $mail_address) {
		$mail_address=trim($mail_address);
		if ($mail_address == '') {
			continue;
		}
		echo "�H�� $mail_address<br/>";
		$mail->AddBCC($mail_address);
	}

	if ($mail->Send()) {
		// �l��H�X
		echo "<div><br><br><center>�q�l���w�H�e<br><br>";
	} else {
		echo $mail->ErrorInfo . "<br/>";
	}
	$mail->ClearBCCs();
}

// �ˬd email �O�_�X�k�����W��ܦ�
// /([a-z0-9_]+|[a-z0-9_]+\.[a-z0-9_]+)@(([a-z0-9]|[a-z0-9]+\.[a-z0-9]+)+\.([a-z]{2,4}))/i
$regex = "/^[0-9a-zA-Z]([-._]*[0-9a-zA-Z])*@[0-9a-zA-Z]([-._]*[0-9a-zA-Z])*\.+[a-zA-Z]+$/";  

for ($i = 0; $i < count($mailer); $i++) {
	echo "<br />";

//	if(!preg_match($regex, $mailer[$i]['email'])) { 
	if(!filter_var($mailer[$i]['email'], FILTER_VALIDATE_EMAIL)) {
		echo "�H�� {$mailer[$i]['email']}, Invalid email address</br>";
	} else { 
		echo "�H�� {$mailer[$i]['email']}, email is valid No.{$i}</br>"; 
	} 

	$content = str_replace("##NAME##", $mailer[$i]['name'], $contains);
	$content = str_replace("##NO##", $mailer[$i]['no'], $content);

	$mail->Body = $content;

	if (!$testonly) {
		//$mail->AddBCC("{$mailer[$i]['email']}");	 // ����̶l��ΦW��
		$mail->AddAddress("{$mailer[$i]['email']}");

		if ($mail->Send()) {
			// �l��H�X
			echo "<div><br><br><center>�q�l���w�H�e<br><br>";
		} else {
			echo $mail->ErrorInfo . "<br/>";
		}
		//$mail->ClearBCCs();
		$mail->ClearAddresses();
	}
}
			
if ($testonly) {
	echo "�H�W mail �ȦC�X�ӡA�å��H�e<br/>";	
}
?>
