<h1>Автодата(autodate)<br><small>(простой плагин для wordpress)</small></h1>
<ul>
    <li><a href="#languages_in_plugin_info">Что использовано в плагине?</a></li>
    <li><a href="#expectations_for_wp_info">Требования плагина к Wordpress</a></li>
    <li><a href="#files_structure_info">Структура плагина</a></li>
    <li><a href="#important_info">ВАЖНАЯ ИНФОРМАЦИЯ!</a></li>
    <li><a href="#general_info">Общая информация о плагине</a>
        <ul>
            <li><a href="#plugin_usage_info">Где он может пригодиться?</a></li>
            <li><a href="#plugin_know_how_info">Как использовать плагин?</a></li>
            <li><a href="#plugin_install_info">Установка плагина</a></li>
        </ul>
    </li>
    <li><a href="#plugin_demo_in_img">Демонстрация работы плагина</a></li>
</ul>
<h2 id="languages_in_plugin_info">Что использовано в плагине?</h2>
<ol>
    <li>PHP>=7.4</li>
    <li>javascript(jQuery>=3.0)</li>
    <li>MySQL</li>
</ol>
<h2 id="expectations_for_wp_info">Требования плагина к Wordpress</h2>
<p>Плагин протестирован со следующими версиями Wordpress:</p>
<ul>
    <li>Мин. версия: 4.5</li>
    <li>Макс. версия: 6.0<br>(актуальная на момент создания readme.md (27.10.2022) )</li>
</ul>
<h2 id="files_structure_info">Структура плагина <small>(см. директорию <a href="https://github.com/MonoBrainCell/autodate/tree/main/src" target="_blank">src</a>)</small></h2>
<ul>
    <li><a href="https://github.com/MonoBrainCell/autodate/blob/main/src/autodate.php" target="_blank">autodate.php</a> - главный файл плагина для Wordpress
    </li>
    <li><a href="https://github.com/MonoBrainCell/autodate/blob/main/src/readme.txt" target="_blank">readme.txt</a> - файл с описанием плагина
    </li>
    <li><a href="https://github.com/MonoBrainCell/autodate/blob/main/src/uninstall.php" target="_blank">uninstall.php</a> - файл, исполняемый перед удаление плагина из Wordpress
    </li>
    <li><a href="https://github.com/MonoBrainCell/autodate/tree/main/src/js_inc" target="_blank">js_inc/</a> - папка с js-файлами, используемыми в плагине
        <ul>
            <li><a href="https://github.com/MonoBrainCell/autodate/blob/main/src/js_inc/autodate_ua_manager.js" target="_blank">autodate_ua_manager.js</a> - файл, используемый в админ. панели для управлением редактором дат плагина</li>
        </ul>
    </li>
    <li><a href="https://github.com/MonoBrainCell/autodate/tree/main/src/languages" target="_blank">languages/</a> - папка с файлами перевода для плагина
    </li>
    <li><a href="https://github.com/MonoBrainCell/autodate/tree/main/src/php_inc" target="_blank">php_inc/</a> - папка с php-файлами, используемыми в плагине
        <ul>
            <li><a href="https://github.com/MonoBrainCell/autodate/blob/main/src/php_inc/autodate.php" target="_blank">autodate.php</a> - файл, содержащий основной класс, реализующий весь функционал плагина</li>
        </ul>
    </li>
</ul>

<h2 id="important_info">ВАЖНАЯ ИНФОРМАЦИЯ!</h2>
<p>Представленный здесь плагин является простой демонстрацией моей работы, как программиста. Если Вы захотите установить этот плагин на свой Wordpress, то просто найдите и установите его через админ. панель Вашего Wordpress.</p>

<h2 id="general_info">Общая информация о плагине</h2>
<p>Данный плагин делает именно, то о чём говорит его название, т.е. добавляет на страницу сайта Wordpress дату и автоматически обновляет её, исходя из заданных настроек</p>
<h3 id="plugin_usage_info">Где он может пригодиться?</h3>
<p>Если на Вашем сайте Wordpress указано множество дат, которые требуется регулярно обновлять, то данным плагин является идеальным решением. Например:</p>
<ol>
    <li>Даты начала/окончания обучения в учебных центрах</li>
    <li>Даты действия скидок на определённые товары</li>
    <li>Даты организации различных общественных мероприятий</li>
    <li>и т.п.</li>
</ol>
<h3 id="plugin_know_how_info">Как использовать плагин?</h3>
<ol>
    <li>Создаёте новую дату в админ. панели плагина</li>
    <li>Определяете все необходимые настройки для неё (не забыв включить дату)</li>
    <li>Сохраняете созданную дату</li>
    <li>Копируете шорткод даты</li>
    <li>Вставляете шорткод в контент туда, где должна быть дата</li>
    <li>Забываете о необходимости постоянно менять даты в контенте</li>
</ol>
<p><strong>P.s.</strong> Дата на сайте отображается в том формате, которым был выбран в основных настройках Wordpress'а</p>
<h3 id="plugin_install_info">Установка плагина</h3>
<p>Скачать, активировать и пользоваться</p>
<h2 id="plugin_demo_in_img">Демонстрация работы плагина</h2>
<div>
    <a href="demo_images/screenshot-7.jpg" title="Посмотреть в оригинальном размере"><img src="demo_images/screenshot-7.jpg"></a>
    <a href="demo_images/screenshot-8.jpg" title="Посмотреть в оригинальном размере"><img src="demo_images/screenshot-8.jpg"></a>
    <a href="demo_images/screenshot-9.jpg" title="Посмотреть в оригинальном размере"><img src="demo_images/screenshot-9.jpg"></a>
    <a href="demo_images/screenshot-10.jpg" title="Посмотреть в оригинальном размере"><img src="demo_images/screenshot-10.jpg"></a>
    <a href="demo_images/screenshot-11.jpg" title="Посмотреть в оригинальном размере"><img src="demo_images/screenshot-11.jpg"></a>
    <a href="demo_images/screenshot-12.jpg" title="Посмотреть в оригинальном размере"><img src="demo_images/screenshot-12.jpg"></a>
</div>