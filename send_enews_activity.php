<html>
<head>
<meta charset="utf-8">
<title>Send ENews</title>
</head>
<body>
<center><p>傳送電子報</p></center>
<form enctype = "multipart/form-data" name="myForm" action="send_activity.php" method="POST" style="text-align:center;">
<b>網頁名稱：</b><input type="text" name="filename" id="filename">ex: index.html</br>
<b>網頁所在路徑：</b><input type="text" name="path" id="path">ex. test/</br>
<input type="checkbox" name="testonly" id="testonly" value="1" checked="1"><label for="testonly">僅列出資料庫Mail</label></input><br/>
<b>E-mail：</b>ex. xxx@gmail.com</br>
<textarea name="email" rows="3" cols="60"></textarea></br>
<b>姓名：</b>ex. xxx@gmail.com</br>
<textarea name="name" rows="3" cols="60"></textarea></br>
<b>序號：</b>ex. 10</br>
<textarea name="no" rows="3" cols="60"></textarea></br>
<input type="submit" name="submit" value="傳送"  style="font-size:24px;font-family:Times New Roman;text-align:center;" />
</form>
</body>
</html>
