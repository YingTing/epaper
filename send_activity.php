<?php
require_once('PHPMailer/PHPMailerAutoload.php');
header("Content-type: text/html; charset=utf-8");

if (!isset($_POST['submit'])) {
	die('Invalid access');
}

//取POST值
$filename = $_POST['filename'];
$path = $_POST['path'];
$email = $_POST['email'];
$testonly = isset($_POST['testonly']);

// 檢查 Path Traversal 攻擊
if (strstr($filename, "..") ||
	strstr($path, "..")) {
	die('Access Denied');
}

$delimiter = "\n";
$rootpath = "/WWW/epaper/";
$url = "//www.mkt.org.tw/";
$contains = '如看不到本封電子報或遺失圖檔，<a href="'.$url."epaper/".$path.$filename.'">【請按此】</a><br/>
			如要取消訂閱電子報，<a href="https://www.mkt.org.tw/cancelepaper">【請按此】</a><br/><br/>';

$mail = new PHPMailer();	// 建立新物件		
//$mail->SetLanguage("zh", 'PHPMailer/language');

//$mail->IsSendmail();						   
$mail->IsSMTP();					// 設定使用SMTP方式寄信
$mail->Host = "localhost";				// 使用 Local SMTP Server
$mail->From = "service@yingting.org.tw";		// 設定寄件者信箱		

$mail->CharSet = "UTF-8";				// 設定郵件編碼   
$mail->Encoding = "base64";
$mail->WordWrap = 50;					// 每50個字元自動斷行
	
$mail->FromName = "YingTing";				// 設定寄件者姓名		
$mail->Subject = "【敬祝】Hello World！";
	
$mail->IsHTML(true);					// 設定郵件內容為HTML 

//取資料庫email
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
	// ********************** 選擇資料庫 ******************************
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

// 先將 email content 準備好
/*** 設定 counter 初始值 ***/
$i = 1;

/*** 開檔 ***/
$fp = fopen($rootpath.$path.$filename, 'r');

$fp2 = fopen($rootpath.'test.txt', 'w');

/*** 不斷 loop pointer ***/
while ( !feof ( $fp) )
{
	/*** 將資料讀入串流中 ***/
	$buffer = stream_get_line( $fp, 1024, $delimiter );
	$t = 'src="'.$url.$path;
	//換src為絕對路徑
	if (strpos ($buffer, 'src="http://')){
		 //nothing to do
		 //echo "$buffer";
	} else if (strpos ($buffer, 'src="//')){
	} else if (strpos ($buffer, 'src="https://')){
	} else {
		//echo "取代src";
		 //換src為絕對路徑
		 $buffer = str_replace('src="',$t,$buffer);
		 //echo "換src為絕對路徑 $buffer";
	}

	$t2 = 'href="'.$url.$path;
	//換href為絕對路徑
	if (strpos ($buffer, 'href="http') || strpos ($buffer, 'href="mailto')){
		 //nothing to do
		 //echo "$buffer";
	} else {
		 //換href為絕對路徑
		 $buffer = str_replace('href="',$t2,$buffer);
		 //echo "換href為絕對路徑 $buffer";
	}
	
	/*** 印出該行的值 ***/
	echo "$buffer";
	
	$contains .= "$buffer";
	fwrite($fp2, $buffer);


	/*** 遞增 counter ***/
	$i++;
	
	/*** 清除記憶體 ***/
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
		echo "寄給 $mail_address<br/>";
		$mail->AddBCC($mail_address);
	}

	if ($mail->Send()) {
		// 郵件寄出
		echo "<div><br><br><center>電子報已寄送<br><br>";
	} else {
		echo $mail->ErrorInfo . "<br/>";
	}
	$mail->ClearBCCs();
}

// 檢查 email 是否合法的正規表示式
// /([a-z0-9_]+|[a-z0-9_]+\.[a-z0-9_]+)@(([a-z0-9]|[a-z0-9]+\.[a-z0-9]+)+\.([a-z]{2,4}))/i
$regex = "/^[0-9a-zA-Z]([-._]*[0-9a-zA-Z])*@[0-9a-zA-Z]([-._]*[0-9a-zA-Z])*\.+[a-zA-Z]+$/";  

for ($i = 0; $i < count($mailer); $i++) {
	echo "<br />";

//	if(!preg_match($regex, $mailer[$i]['email'])) { 
	if(!filter_var($mailer[$i]['email'], FILTER_VALIDATE_EMAIL)) {
		echo "寄給 {$mailer[$i]['email']}, Invalid email address</br>";
	} else { 
		echo "寄給 {$mailer[$i]['email']}, email is valid No.{$i}</br>"; 
	} 

	$content = str_replace("##NAME##", $mailer[$i]['name'], $contains);
	$content = str_replace("##NO##", $mailer[$i]['no'], $content);

	$mail->Body = $content;

	if (!$testonly) {
		//$mail->AddBCC("{$mailer[$i]['email']}");	 // 收件者郵件及名稱
		$mail->AddAddress("{$mailer[$i]['email']}");

		if ($mail->Send()) {
			// 郵件寄出
			echo "<div><br><br><center>電子報已寄送<br><br>";
		} else {
			echo $mail->ErrorInfo . "<br/>";
		}
		//$mail->ClearBCCs();
		$mail->ClearAddresses();
	}
}
			
if ($testonly) {
	echo "以上 mail 僅列出來，並未寄送<br/>";	
}
?>
