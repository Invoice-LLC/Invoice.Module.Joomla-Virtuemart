<h1>Invoice Virtuemart Plugine</h1>

<h3>Установка</h3>

1. [Скачайте плагин](https://github.com/Invoice-LLC/Invoice.Module.Joomla-Virtuemart.git) и скопируйте содержимое архива в корень сайта
2. Перейдите во вкладку **Virtuemart->Payment methods**
![Imgur](https://imgur.com/Z4dR9eR.png)
3. Нажмите **new** и введите данные как на скриншоте
![Imgur](https://imgur.com/IeEpqoR.png)
3. Перейдите во вкладку **Configuration**, затем введите логин от личного кабинета и API ключ, после чего сохраните настройки
![Imgur](https://imgur.com/wUiCqEe.png)
4. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
с типом **WebHook** и адресом: **%URL сайта%/index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component&format=json**
![Imgur](https://imgur.com/LZEozhf.png)
