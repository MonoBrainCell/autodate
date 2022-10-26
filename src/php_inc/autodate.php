<?php
/* * Введение

	- - - Свойства и константы - - -
	
	> JS_HANDLER_FILE - путь до js-файла, используемого при редактировании дат, с которыми работает плагин
	
	> DB_TABLE_POSTFIX - имя таблицы в БД, используемой плагином
	
	> AJAX_ANSWER_SEPARATOR - разделитель в строке ответа от метода класса на ajax-запрос
	
	> SHORTCODE_NAME - имя шорткода, используемого для вставки даты
	
	> ACTIV_DEACTIV_DATA - информация об обработчиках активации(включении) и деактивации(выключения) плагина
	
	> ACT_AND_FUNC_DATA - информация о хуках и функциях обработчиках для них. Структура:
		[
			"часть_функционала"=>["хук","имя_функции_обработчика"],
			...
		]
	
	> SHORTCODE_DATA - информация относящаяся к шорткоду плагина и его обработке wordpress'ом
	
	> $js_handler_file_path - полный путь до js-файла, используемого при редактировании дат
	
	> $wpdb_obj - объект wordpress'а, отвечающий за взаимодействие с его БД
	
	> $table_name - имя таблицы БД, используемой данным плагином
	
	> $date_format - формат даты
	
	
	- - - Методы - - -
	
	> конструктор - готовит объект для использования(заполняет все переменные необходимыми значениями).
	
	> start() - метод, используемый в качестве обработчика хука активации плагина.
	
	> stop() - метод, используемый в качестве обработчика хука деактивации плагина.
	
	> intergrate() - метод, используемый в качестве обработчика хука меню админ. части wordpress'а(добавляем пункт, относящийся к плагину в админ. меню).
	
	> manager_javascript() - метод, используемый в качестве обработчика хука добавления js в админ. части wordpress (добавляем js-файл, помогающий управлять информацией о датах в рамках нашего плагина).
	
	> manager_delete_callback() - метод, используемый в качестве обработчика ajax-запроса на удаление отдельной даты.
		Возвращаемые значения: строка - указывает на успешность удаления даты
		(В данном случае возвращаемым значением являются данные выведенные в документ (echo,print_r и т.п.) в рамках исполнения метода)
	
	> manager_callback() - метод, используемый в качестве обработчика ajax-запроса на изменение отдельной даты.
		Возвращаемые значения: строка - указывает на успешность сохранения изменений даты
		(В данном случае возвращаемым значением являются данные выведенные в документ (echo,print_r и т.п.) в рамках исполнения метода)
	
	> handle_shortcode($atts) - метод, используемый в качестве обработчика изменения шорткода(замена шорткода на конкретную дату).		
		Передаваемые аргументы:
			$atts - все атрибуты и их значения, которые были указаны в шорткоде
				Тип: ассоциативный массив (["атрибут"=>значение,...])
		Возвращаемые значения: строка - html-код, с датой, которая должна быть вставлена вместо шорткода
	
	> audit_date_data($dataSlice) - метод, используемый для проверки даты на необходимость её изменения. Изменяет дату и сохраняет её. Действует аналогично wp-cron.		
		Передаваемые аргументы:
			$dataSlice - данные из таблицы плагина, относящиеся к целевой дате
				Тип: ассоциативный массив		
		Возвращаемые значения: массив - обработанные данные, относящиеся к целевой дате
	
	> create_admin_page() - метод, используемый как обработчик перехода к странице плагина, указанной в админ. меню (создаём админ. страницы для управления датами плагина).
	
	
	- - - Таблица БД - - -
	
	Столбцы таблицы:
		> id - идентификатор даты
		
		> short_desc - краткое пояснение для даты
		
		> target_date - дата, которая должна отображаться на сайте
		
		> target_offset - количество дней на которые следует "сдвинуть" дату
		
		> update_date - ближайшая дата, когда нужно изменить значение отображаемой даты
		
		> update_interval - интервал обновления даты, отображаемой на сайте
		
		> is_running - флаг, определяющий является ли дата действующей(активной)
*/
class Autodate {	
	protected const JS_HANDLER_FILE="../js_inc/autodate_ua_manager.js";	
	protected const DB_TABLE_POSTFIX="autodate_data";
	protected const AJAX_ANSWER_SEPARATOR="-|-";
	protected const SHORTCODE_NAME="autodate";
	
	public const ACTIV_DEACTIV_DATA=array(
		"activation"=>array(false,"start"),
		"deactivation"=>array(false,"stop")
	);
	
	public const ACT_AND_FUNC_DATA=array(
		"menu"=>array("admin_menu","intergrate"),
		"ajax_main"=>array("wp_ajax_autodate_manager","manager_callback"),
		"ajax_delete"=>array("wp_ajax_autodate_delete_manager","manager_delete_callback"),
		"js_add"=>array("admin_enqueue_scripts","manager_javascript")
	);
	
	public const SHORTCODE_DATA=array(self::SHORTCODE_NAME,"handle_shortcode");
	
	protected $js_handler_file_path;
	static public $wpdb_obj;
	static public $table_name;
	static public $date_format;
	
	
	public function __construct(){
		// Делаем доступной внутри метода переменную wordpress'а, отвечающую за взаимодействие с БД и передаём её значение свойству нашего класса
		global $wpdb;
		self::$wpdb_obj=$wpdb;
		// Задаём свойство нашего класса, отвечающее за имя таблицы БД, с которой работает на плагин
		self::$table_name=self::$wpdb_obj->get_blog_prefix().self::DB_TABLE_POSTFIX;
		
		// Задаём свойство объекта класса, отвечающее за полный путь до js-файла, используемого для управления датами в админ. части плагина
		$this->js_handler_file_path=plugins_url(self::JS_HANDLER_FILE,__FILE__);
		// Задаём свойство нашего класса, отвечающее за формат даты, в виде используемой в wordpress'е
		self::$date_format=get_option("date_format","Y-m-d");
	}
	
	public function start(){
		// Подключаем файл, чтобы в дальнейшем использовать функцию dbDelta
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		// Формируем строку кодировку для запроса в БД
		$charset_collate = "DEFAULT CHARACTER SET ".self::$wpdb_obj->charset." COLLATE ".self::$wpdb_obj->collate;
		// Пишем запрос к БД по созданию таблицы, ТОЛЬКО в случае, если её не существут
		$sql="CREATE TABLE IF NOT EXISTS `".self::$table_name."` (
`id` bigint(20) unsigned AUTO_INCREMENT NOT NULL,
`short_desc` varchar(255) DEFAULT NULL,
`target_date` varchar(10) DEFAULT NULL,
`target_offset` smallint(5) unsigned DEFAULT NULL,
`update_date` varchar(10) DEFAULT NULL,
`update_interval` smallint(5) unsigned DEFAULT NULL,
`is_running` tinyint(1) unsigned NOT NULL DEFAULT '0',
PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 {$charset_collate}";
		
		// Отправляем запрос по созданию таблицы в БД, с использование спец. функции wordpress
		dbDelta($sql);
	}
	
	public function stop(){ return true; }
	
	public function intergrate(){
		// Добавляем пункт в админ. меню; в качестве метода формирующего админ. страницу, соответствующую пункту меню определяем create_admin_page
		add_menu_page(__('Autodate management','autodate'), __('Autodate','autodate'), 'manage_options','autodate',array($this,"create_admin_page"));
	}
	
	public function manager_javascript(){
		// Подключаем в админ. части js-файл, используемый при редактировании дат
		wp_enqueue_script("autodate_ajax_js",$this->js_handler_file_path,array('jquery'),false,true);
	}
	
	public function manager_delete_callback(){
		// Проверяем наличие поля id в запросе, если его нет - выводим оповещение в документ и прерываем исполнение php-файла
		if(isset($_POST['id'])===false){
			echo "data corrupted";
			wp_die();
		}
		
		// Отправляем запрос на удаление строки из таблицы БД, с использованием спец. метода wp-объекта работающего с БД (2-й аргумент: массив с условиями для поиска строк для удаления(["имя_столбца"]=>значение_ячейки_в_столбце); 3-й аргумент: массив с типами данных, соответствующих 2-му аргументу)
		$result=self::$wpdb_obj->delete(self::$table_name,array("id"=>(int)$_POST['id']), array("%d") );
		
		// Если запросом не было затронуто ни одной строки в таблице, говорим, что запрос был проведён "в холостую"
		if ($result<1){
			echo "idle request";
		}
		else {
			echo "success";
		}
		
		wp_die();
	}
	
	public function manager_callback(){	
		// Проверяем все ли необходимые поля были переданы в ходе запроса, если нет - выводим в документ контент, соответствующий ошибке и прерываем исполнение php-файла
		if(isset($_POST['id'])===false || isset($_POST['short_desc'])===false || isset($_POST['update_date'])===false || isset($_POST['update_interval'])===false || isset($_POST['target_date'])===false || isset($_POST['target_offset'])===false || isset($_POST['is_running'])===false){
			echo "none". self::AJAX_ANSWER_SEPARATOR .__("An error occurred while transferring data.\n Refresh the Autodate page and try changing the data again",'autodate');
			wp_die();
		}
		
		// Ассоциативный массив для проверки данных, внесения изменений в БД и формирование информации о возникающих ошибках. В качестве ключей массива указаны имена полей из запроса
		$data_pattern=array(
			"short_desc"=>array(
				"type"=>"simple_str",// Формат значения поля: простая строка(simple_str) / строка с датой(date_str) / интервал в виде числа(interval_num) / "булевое" значение в виде числа(bool_num)
				"db_type"=>"%s", // Тип данных для таблицы БД
				"db_col_name"=>"short_desc", // Имя столбца в таблице БД
				"error"=>__("Failed to update data for field *Short description*",'autodate') // Текст ошибки, связанной с этим полем
			),
			"update_date"=>array(
				"type"=>"date_str",
				"db_type"=>"%s",
				"db_col_name"=>"update_date",
				"error"=>__("Failed to update data for field *When to update the date*",'autodate')
			),
			"update_interval"=>array(
				"type"=>"interval_num",
				"db_type"=>"%d",
				"db_col_name"=>"update_interval",
				"error"=>__("Failed to update data for field *Update frequency*",'autodate')
			),
			"target_date"=>array(
				"type"=>"date_str",
				"db_type"=>"%s",
				"db_col_name"=>"target_date",
				"error"=>__("Failed to update data for field *Displayed date*",'autodate')
			),
			"target_offset"=>array(
				"type"=>"interval_num",
				"db_type"=>"%d",
				"db_col_name"=>"target_offset",
				"error"=>__("Failed to update data for field *How much to shift the date*",'autodate')
			),
			"is_running"=>array(
				"type"=>"bool_num",
				"db_type"=>"%d",
				"db_col_name"=>"is_running",
				"error"=>__("Failed to update the state of switch *Switched on/Switched off*",'autodate')
			)
			
		);
		
		$data_arr=array(); // массив для данных, которые будут использоваться при запросе к БД по изменению/добавлению даты
		$data_format_arr=array(); // массив для форматов, которые соответствуют данным добавляемым в массив $data_arr
		$data_err=array(); // массив для ошибок, которые были выявлены в результате попытки изменения/добавления даты 
		
		// Обходим циклом массив проверки данных ($field-имя поля, $settings-доп. данные для обработки значения поля)
		foreach($data_pattern as $field=>$settings){
			// Определяем последовательность действий исходя из формата значения
			switch($settings['type']){
				// Если тип соответствует обычной строке
				case "simple_str":
				// Очищаем полученное из запроса значение, используя спец. wp-функцию
					$_POST[$field]=sanitize_text_field($_POST[$field]);
					// Если значение не пустое, то в массив данных для запроса к БД добавляем значение, а в качестве ключа указываем имя столбца в БД; также добавляем в массив форматов обозначение формата, соответствующее этому полю
					if(strlen($_POST[$field])>0){
						$data_arr[$settings['db_col_name']]=$_POST[$field];
						array_push($data_format_arr,$settings['db_type']);
					}
					// Если значение пустое, то добавляем в массив ошибок, строку соответствующую ошибке для этого поля
					else {
						array_push($data_err,$settings['error']);
					}
				break;
				// Если тип соответствует строке даты
				case "date_str":
					$_POST[$field]=sanitize_text_field($_POST[$field]);
					// Проверяем значение на соответствие регулярке; пример подходящий под целевую регулярку: 0000-00-00 ИЛИ 2021-5-16
					$preg_result=preg_match("/^[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}$/",$_POST[$field]);
					// Если проверка по регулярке прошла успешно
					if($preg_result!==false || $preg_result>0){
						$data_arr[$settings['db_col_name']]=$_POST[$field];
						array_push($data_format_arr,$settings['db_type']);
					}
					else {
						array_push($data_err,$settings['error']);
					}
				break;
				// Если тип соответствует интервалу в виде числа
				case "interval_num":
					// Кроме очистки значения приводим его к целочисленному типу
					$_POST[$field]=(int)sanitize_text_field($_POST[$field]);
					// Если значение положительное
					if ($_POST[$field]>0){
						$data_arr[$settings['db_col_name']]=$_POST[$field];
						array_push($data_format_arr,$settings['db_type']);
					}
					else {
						array_push($data_err,$settings['error']);
					}
				break;
				// Если тип соответствует "булевому" значению в виде числа
				case "bool_num":
					$_POST[$field]=sanitize_text_field($_POST[$field]);	
					// Если значение говорит о том, что дата должна быть включена
					if($_POST[$field]=="enb"){
						// В качестве значения указываем 1
						$data_arr[$settings['db_col_name']]=1;
						array_push($data_format_arr,$settings['db_type']);
					}	
					// Если значение говорит о том, что дата должна быть выключена
					else if($_POST[$field]=="dsb") {
						// В качестве значения указываем 1
						$data_arr[$settings['db_col_name']]=0;
						array_push($data_format_arr,$settings['db_type']);
					}
					else {
						array_push($data_err,$settings['error']);
					}
					// Прим. относительно типа: Т.к. БД не поддерживает чистые булевые значения true/false следует заменять их на соответствующие числовые значения
				break;
			}
		}
		
		$_POST["id"]=sanitize_text_field($_POST["id"]);
		// Проверяем переданный id даты на соответствие регулярке, указывающей, что переданные данные соответствуют новой дате
		$preg_result=preg_match("/^new/",$_POST["id"]);
		// Если данные соответствуют уже существующей дате, то...
		if ($preg_result===false || $preg_result<1){
			$action="update"; // Определяем тип обращения к БД, как изменение существующих данных
			// Отправляем запрос на изменение к БД, с использованием спец. метода wp-объекта работающего с БД (2-й аргумент - на что меняем(["имя_столбца"=>новое значение]); 3-й аргумент - где меняем(условие)(["имя_столбца"=>новое значение]); 4-й аргумент - тип данных для значений, которые заменят текущие; 5-й аргумент - тип данных для условия )
			$result=self::$wpdb_obj->update(self::$table_name,$data_arr,array("id"=>(int)$_POST['id']),$data_format_arr,array("%d"));
		}
		// Если данные соответствуют уже существующей дате, то...
		else {
			$action="insert"; // Определяем тип обращения к БД, как добавление новых данных
			// Отправляем запрос на добавление к БД, с использованием спец. метода wp-объекта работающего с БД (2-й аргумент - что добавляем(["имя_столбца"=>новое значение]); 3-й аргумент - тип данных для добавляемых значений)
			$result=self::$wpdb_obj->insert(self::$table_name,$data_arr,$data_format_arr);
		}
		
		// Если запрос был успешен и удалось внести изменения в таблицу БД
		if (is_bool($result)===false && $result>0){
			// При этом запрос к БД был отправлен для добавления новой даты
			if($action=="insert"){
				// Формируем новый запрос, но получение id только что добавленной даты(необходимо для обновления id редактируемой даты без перезагрузки редактора)
				$sql="SELECT max(`id`) as last_id FROM `".self::$table_name."`;";
				$last_id_rqst_result=self::$wpdb_obj->get_results($sql,ARRAY_A);
				
				// Если id получить не удалось, то говорим, что возникла проблема в процессе сохранения данных
				if($last_id_rqst_result==NULL || count($last_id_rqst_result)<1){
					echo "none". self::AJAX_ANSWER_SEPARATOR .__("An error occured while saving your changes.\n Refresh the plugin page",'autodate');
				}
				// Если всё хорошо, то выводим в документ id и текстовое оповещения, что всё ОК
				else {
					echo esc_attr($last_id_rqst_result[0]['last_id']). self::AJAX_ANSWER_SEPARATOR  .__("New date information has been saved",'autodate');
				}
			}
			// Запрос был отправлен для обновления уже существующих данных
			else {
				echo "old". self::AJAX_ANSWER_SEPARATOR .__("Data changes saved",'autodate');
			}
		}
		// Если в процессе запроса к БД что-то пошло не так
		else {
			// Если запросом было обновление данных и не было обновлено ни одной строки($result==0), то всё равно выдаём сообщение об успешном сохранении данных
			if($action=="update" && is_bool($result)===false){
				echo "old". self::AJAX_ANSWER_SEPARATOR .__("Data changes saved",'autodate');
			}
			// Во всех остальных случаях выводим в документ сообщение об ошибке
			else {
				echo "none". self::AJAX_ANSWER_SEPARATOR .__("An error occurred while saving data.\n Refresh the Autodate page and try changing the data again",'autodate');
			}
			// Прим. относительно update: данный подход подразумевает, что html-код страницы редактора дат сформирован корректно
		}
		// Если массив с ошибками не пустой, то обходим его циклом и выводим в документ все имеющиеся ошибки
		if (empty($data_err)===false){
			echo "\n--------------------------------\n";
			for ($a=0,$b=count($data_err);$a<$b;$a++){
				echo "\n".esc_attr($data_err[$a]);
			}
		}

		wp_die();
	}
	
	public function handle_shortcode($atts){
		// Получаем все атрибуты, указанные в шорткоде, при этом определив имя целевого атрибута и его дефолтное значение(["имя_атриб"=>деф_знач])
		$atts=shortcode_atts(array("id"=>1),$atts);
		
		// Формируем и отправляем запрос к БД для получения информации о дате по её id, указанному в атрибуте шорткода
		$sql="SELECT `id`,`target_date` as td,`target_offset` as toff,`update_date` as ud,`update_interval` as ui,`is_running` as ir FROM `".self::$table_name."` WHERE `id`={$atts['id']}";
		$target_date=self::$wpdb_obj->get_results($sql,ARRAY_A);
		
		// Если не удалось получить ответ от БД ИЛИ в ответе содержатся не все необходимые данные, то возвращаем пустую строку
		if ($target_date==NULL || $target_date[0]['td']==NULL || $target_date[0]['toff']==NULL || $target_date[0]['ud']==NULL || $target_date[0]['ui']==NULL || $target_date[0]['ir']==NULL){
			return "";
		}
		
		// Проверяем дату на её актуальность и необходимость обновления и используем полученный результат в качестве основы создания объекта дата-время
		$target_date[0]=self::audit_date_data($target_date[0]);
		$returned_date=new DateTime($target_date[0]['td']);
		
		// Возвращаем дату приведённую к требуемому формату, при этом фильтруем её от спец. символов при помощи wp-функции
		return esc_attr($returned_date->format(self::$date_format));
	}
	
	public static function audit_date_data($dataSlice){
		// Формируем 2 объекта дата-время: по текущей метке времени и по дате следующего изменения отображаемой даты
		$current_time=new DateTime(current_time("Y-m-d"));
		$update_time=new DateTime($dataSlice['ud']);
		
		// Вычисляем разницу между текущей моментом и датой обновления
		$time_delta=$current_time->diff($update_time);
		// Если текущая дата определилась позднее даты обновления ИЛИ текущая дата совпала с датой обновления
		if ($time_delta->invert>0 || ($time_delta->invert<1 && $time_delta->days==0)){
			// Формируем объект дата-время на основе отображаемой даты
			$target_time=new DateTime($dataSlice['td']);
			
			// Вычисляем нужно ли будет добавить дополнительную единицу в дальнейшем определении множителя дней, при формировании новых дат(если количество "пропущенных" дней и интервал обновления окажутся кратными, то после изменения даты на вычисленное количество дней мы упрёмся в необходимость повторного обновления даты)
			if ($time_delta->days%$dataSlice['ui']==0){
				$additional_num=1;
			}
			else {
				$additional_num=0;
			}
			// Вычисляем сколько интервалов нужно будет добавить к отображаемой дате и дате обновления
			$interval_factor=ceil($time_delta->days/$dataSlice['ui'])+$additional_num;
			
			// Вычисляем количество добавляемых дней к отображаемой дате и дате обновления и формируем на их основе объекты интервала дат
			$update_date_offset=$interval_factor*$dataSlice['ui']; 
			$target_date_offset=$interval_factor*$dataSlice['toff']; 
			$update_span=new DateInterval("P{$update_date_offset}D");
			$target_span=new DateInterval("P{$target_date_offset}D");
			
			// Добавляем дни к датам и формируем из них строки соответствующего формата
			$update_time->add($update_span);
			$target_time->add($target_span);
			$temp_ud=$update_time->format("Y-m-d");
			$temp_ti=$target_time->format("Y-m-d");
			
			// Отправляем запрос на изменение отображаемой даты и даты обновления в таблице БД 
			$result=self::$wpdb_obj->update(
				self::$table_name,
				array("target_date"=>$temp_ti,"update_date"=>$temp_ud),
				array("id"=>(int)$dataSlice['id']),
				array("%s","%s"),
				array("%d")
			);
			// Если запрос произведён успешно и информация о датах изменена, то в массиве с данными изменяем даты на те, которые были вычислены выше
			if($result!==false && $result>0){
				$dataSlice['ud']=$update_time->format("Y-m-d");
				$dataSlice['td']=$target_time->format("Y-m-d");
			}
			return $dataSlice;
		}
		// Если ещё не пришло время обновлять даты, то просто возвращаем тот же массив, что был передан в метод
		else {
			return $dataSlice;
		}
	}
	
	public function create_admin_page(){
		// Проверяем права доступа текущего пользователя админ. части
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.','autodate' ) );
		}
		// Формируем запрос на получение всех данных из таблицы БД с датами и отправляем его
		$sql="SELECT * FROM `".self::$table_name."`";
		$units=self::$wpdb_obj->get_results($sql,ARRAY_A);
		
		// Формируем переменную js с данными по всем существующим датам
		$data_js_arr="let dateListData=[";
		for($a=0,$b=count($units);$a<$b;$a++){
			// Если в текущей позиции полученных данных существуют обе даты и интервалы, связанные с ними, то добавляем в массив ячейки с ключами, ожидаемыми методом audit_date_data, а в качестве значений даём ссылки на значения существующих ячеек такого же применения; Проводим проверку даты на необходимость обновления и обновляем если нужно
			if ($units[$a]['target_date']!=NULL && $units[$a]['target_offset']!=NULL && $units[$a]['update_date']!=NULL && $units[$a]['update_interval']!=NULL){
				$units[$a]['td']=&$units[$a]['target_date'];
				$units[$a]['toff']=&$units[$a]['target_offset'];
				$units[$a]['ud']=&$units[$a]['update_date'];
				$units[$a]['ui']=&$units[$a]['update_interval'];
				$units[$a]=self::audit_date_data($units[$a]);
			}
			// Добавляем в js-массив объект, с 2-мя свойствами: id и shortDesc и передаём им соответствующие значения полученные из таблицы БД
			$data_js_arr.="{id:{$units[$a]['id']},shortDesc:'{$units[$a]['short_desc']}'";
			// Определяем значения для состояния даты(вкл/выкл)
			if($units[$a]['is_running']==NULL || $units[$a]['is_running']<1){
				$enb_flag="";
				$dsb_flag=" checked=\'checked\'";
			}
			else {
				$enb_flag=" checked=\'checked\'";
				$dsb_flag="";
			}
			// Добавляем во всё ещё открытый js-объект значения для свойств определяющих вкл. и выкл. отображаемая дата
			$data_js_arr.=",enbFlag:'{$enb_flag}',dsbFlag:'{$dsb_flag}'";
			
			// Если дата обновления не определена, то указываем в её качестве текущую дату
			if($units[$a]['update_date']==NULL){
				$ud_value=current_time("Y-m-d");
			}
			else {
				$ud_value=$units[$a]['update_date'];
			}
			// Добавляем во всё ещё открытый js-объект информацию по дате обновления
			$data_js_arr.=",udValue:'{$ud_value}'";
			
			if($units[$a]['update_interval']==NULL){
				$ui_intl=1;
			}
			else {
				$ui_intl=$units[$a]['update_interval'];
			}
			$data_js_arr.=",uiIntl:{$ui_intl}";
				
			if($units[$a]['target_date']==NULL){
				$td_value=current_time("Y-m-d");
			}
			else {
				$td_value=$units[$a]['target_date'];
			}
			$data_js_arr.=",tdValue:'{$td_value}'";
				
			if($units[$a]['target_offset']==NULL){
				$to_intl=1;
			}
			else {
				$to_intl=$units[$a]['target_offset'];
			}
			$data_js_arr.=",toIntl:{$to_intl}},";// закрываем вложенный в js-массив объект
		}
		$data_js_arr.="];"; // закрываем js-массив
		$data_js_arr.="\nlet currentDate='".current_time("Y-m-d")."';"; // добавляем js-переменную с текущей датой
		// Добавляем js-переменную со списком сообщений для различных событий редактора (сделано в большей степени для того, чтобы не замарачиваться с реализацией перевода для js)
		$data_js_arr.="\nlet messages={shortCode:'".__("does not exist",'autodate')."',flagEnabled:'".__("Switched on",'autodate')."',flagDisabled:'".__("Switched off",'autodate')."',saveButton:'".__("Save changes",'autodate')."',deleteButtonTip:'".__("Delete this data",'autodate')."',confirmQuestion:'".__("Are you sure you want to delete this data?",'autodate')."',deleteFailMsg:'".__("Data has not been deleted!\\n Refresh the Autodate page and try again",'autodate')."',deleteSuccessMsg:'".__("Data deleted successfully",'autodate')."',questionForAdd:'".__("Want to add new data?",'autodate')."'};";
		// Добавляем сформированный js в админ. часть для нашего редактора
		wp_add_inline_script("autodate_ajax_js",$data_js_arr);
		// Формируем и добавляем CSS в админ. часть для нашего редактора
		wp_register_style( 'autodate_custom_style', false );
		wp_enqueue_style('autodate_custom_style');
		wp_add_inline_style("autodate_custom_style","tr.special_wpdu_row_attention{outline:2px solid #faa;} .wp-core-ui .delete_interval_button {color: #f00; border-color: #f00;} table.tips-list {box-shadow: none; background-color: transparent; border: none;} table.tips-list tr.tips-row { background-color: transparent; } table.tips-list col { width:40%; } table.tips-list .widgets-holder-wrap { padding:8px; } .autodate-unordered-list{ margin-left: 3%; }");
		// Выводим весь необходимый для редактирования дат html-код
		echo "<h1>".__("Autodate management",'autodate')."</h1><p>".__("On this plugin page you can: manage automatically updated dates, create new ones, delete existing ones",'autodate')."</p>";
		echo "<div class='wrap'>
	<table class='wp-list-table widefat striped tips-list'>
		<col>
		<col>
		<tr class='tips-row'>
			<td>
				<div class='widgets-holder-wrap closed' style=''>
					<div class='widgets-sortables ui-droppable'>
						<div class='sidebar-name'>
							<button type='button' class='handlediv hide-if-no-js' aria-expanded='true'>
								<span class='screen-reader-text'>".__("Autodate usage hint",'autodate')."</span>
								<span class='toggle-indicator'></span>
							</button>
							<h2>".__("How to use Autodate",'autodate')." <span class='spinner'></span></h2>
						</div>
						<div class='description'>
							<p>".__("To use the plugin, you need to take a few steps:",'autodate')."</p>
							<ol>
								<li>".__("Create new date (button <strong>Add date</strong>)",'autodate')."</li>
								<li>".__("Customize the date:",'autodate')."
									<ol>
										<li>".__("Specify the value of the <strong>Displayed date</strong>",'autodate')." </li>
										<li>".__("Specify the value of the <strong>How much to shift the date</strong>",'autodate')."</li>
										<li>".__("Specify the value of the <strong>When to update the date</strong>",'autodate')."</li>
										<li>".__("Specify the value of the <strong>Update frequency</strong>",'autodate')."</li>
										<li>".__("For further convenience of editing the date, it is recommended to specify a value for the <strong>short description</strong>",'autodate')."</li>
									</ol>
								</li>
								<li>".__("Switch on date (<strong>Switched on/Switched off</strong>)<br><em>Please note that if the target date is <strong>switched off</strong>, then it will not be displayed on the site, BUT it will be updated in accordance with the specified settings.</em>",'autodate')."</li>
								<li>".__("Save the date (button <strong>Save changes</strong>)",'autodate')."</li>
								<li>".__("Copy the generated <strong>shortcode</strong>",'autodate')."</li>
								<li>".__("Add <strong>shortcode</strong> to post or page content<br><em>Please note that the date will be displayed in the format specified in your Wordpress settings ( <strong>Settings</strong> --> <strong>General</strong> --> item <strong>Date format</strong> )</em><br>P.S. Please note that the <strong style='color: #f00;'>Autodate only works with the date</strong>, data that determine the time (hours, minutes, seconds) are not taken into account.",'autodate')."</li>
							</ol>
							<p>".__("You can also:",'autodate')."</p>
							<ul class='autodate-unordered-list'>
								<li>".__("Delete previously created dates (red button <strong>X</strong>)",'autodate')."</li>
								<li>".__("Edit settings of previously created dates (Remember to save your changes - button <strong>Save changes</strong>)",'autodate')."</li>
							</ul>
						</div>
					</div>
				</div>
			</td>
			<td>
				<div class='widgets-holder-wrap closed'>
					<div class='widgets-sortables ui-droppable'>
						<div class='sidebar-name'>
							<button type='button' class='handlediv hide-if-no-js' aria-expanded='true'>
								<span class='screen-reader-text'>".__("How to manage date settings",'autodate')."</span>
								<span class='toggle-indicator'></span>
							</button>
							<h2>".__("How to manage date settings",'autodate')." <span class='spinner'></span></h2>
						</div>
						<div class='description'>
							<p>".__("Let's say the Autodate settings fields contain the following values:",'autodate')."</p><ul class='autodate-unordered-list'><li>".__("<strong>Displayed date:</strong> 15.01.2021",'autodate')."</li><li><strong>".__("How much to shift the date",'autodate')."</strong>: 10</li><li>".__("<strong>When to update the date:</strong> 13.01.2021",'autodate')."</li><li><strong>".__("Update frequency",'autodate')."</strong>: 10</li></ul><p>".__("This means that until <strong>13.01.2021</strong> the website will display the date <strong>15.01.2021</strong>.<br>From <strong>13.01.2021</strong> to <strong>22.01.2021</strong> <em>(13.01 + 10 is the date of the next update)</em> the date on the website will be as follows: <strong>25.01.2021</strong> <em>(15.01 + 10 is the date shift)</em>",'autodate')."</p>
						</div>
					</div>
				</div>
			</td>
		</tr>
	</table>
</div> ";
		echo "<div class='wrap'><table class='wp-list-table widefat striped table-view-list' id='date_intervals_list'>\n<thead><tr>".__("<th>Switch on/Switch off </th><th>Shortcode</th><th>Short description</th><th>Displayed date</th><th>How much to shift the date</th><th>When to update the date</th><th>Update frequency</th>",'autodate')."<th></th><th></th></tr></thead></table><br><span class='button button_primary' data-new-counter='0' id='add_interval_date'>".__("Add date",'autodate')."</span></div>";		
	}
}