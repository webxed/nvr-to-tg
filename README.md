# nvr-to-tg
Shots and video sender for DVR/NVR (netsurveillance) devices to Telegram use [FileZilla](https://filezilla.ru/documentation/FileZilla_FTP_Server) ftp server on MS Windwos OS computers

Usage
1. SetUp PHP by [manual](https://www.geeksforgeeks.org/how-to-install-php-in-windows-10/) or use local web-server ex. https://ospanel.io
2. Clone repo to yours Windows PC or save files separetly
3. Fill yours settings on **filezilla_log_parser.settings** file
4. Run script **filezilla_log_parser.php** and debug if need
5. Set up CRON task in [OSpanel](https://ospanel.io) or in MS Windows [Task Scheduler](https://www.windowscentral.com/how-create-automated-task-using-task-scheduler-windows-10) for every 5 min for ex.
   
` */5 * * * * %progdir%\modules\wget\bin\wget.exe -q --no-cache http://localhost/tg_send_tempdata.php -O %progdir%\userdata\temp\tg_tempsend.txt`

----
# nvr-to-tg
Скрипт для отпрвки снимков и видео фрагментов от видеорегистратора в Телеграм, на компьютере под управлением Windows.

Использование
1. Для работы потребуется утсновить PHP (php-cli), либо локальный веб-сервер, например https://ospanel.io
2. Скачать данный рапозиторий или файлы из него отдельно
3. Прописать настройки в файле **filezilla_log_parser.settings**
4. Проверить работу основного **filezilla_log_parser.php**
5. Поместить задачу в CRON используя возможности [OSpanel](https://ospanel.io) или MS Windows [Task Scheduler](https://www.windowscentral.com/how-create-automated-task-using-task-scheduler-windows-10)
