<h1>Invoice Virtuemart Plugin</h1>

**Перед установкой обязательно проверьте, что оплата совершается в рублях.**

<h3>Установка</h3>

1. [Скачайте плагин](https://github.com/Invoice-LLC/Invoice.Module.Joomla-Virtuemart/archive/master.zip)
2. Перейдите во вкладку **Расширения->Управлять->Установить** и загрузите скачаный архив
![Imgur](https://imgur.com/xmXmLtj.png)
3. Перейдите во вкладку **Virtuemart->Payment methods**<br>
![Imgur](https://imgur.com/Z4dR9eR.png)
4. Нажмите **new** и введите данные как на скриншоте
![Imgur](https://imgur.com/IeEpqoR.png)
5. Перейдите во вкладку **Configuration**, затем введите логин от личного кабинета и API ключ, после чего сохраните настройки
![image](https://user-images.githubusercontent.com/91345275/203314147-aa7dbbde-4141-497b-a3f6-365701d88297.png)<br>
<br>Api ключ и Merchant Id в [личном кабинете Invoice](https://lk.invoice.su/):<br>
![image](https://user-images.githubusercontent.com/91345275/196218699-a8f8c00e-7f28-451e-9750-cfa1f43f15d8.png)
![image](https://user-images.githubusercontent.com/91345275/196218722-9c6bb0ae-6e65-4bc4-89b2-d7cb22866865.png)<br>
<br>Terminal Id:<br>
![image](https://user-images.githubusercontent.com/91345275/196218998-b17ea8f1-3a59-434b-a854-4e8cd3392824.png)
![image](https://user-images.githubusercontent.com/91345275/196219014-45793474-6dfa-41e3-945d-fc669c916aca.png)<br>
6. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
с типом **WebHook** и адресом: **%URL сайта%/index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&format=json**
![Imgur](https://imgur.com/LZEozhf.png)
