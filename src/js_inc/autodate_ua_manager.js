jQuery(document).ready(function($){
	// Объявляем функцию, которая будет формировать строки в таблице нашего редактора дат
	//		tableRow - элемент строки таблицы, в которую будут добавляться ячейки
	//		dataSlice - набор данных под одной дате
	function createListRow(tableRow,dataSlice){
		let shortCode;
		// Приводим id к строковому типу и проверяем есть ли в нём указание на новую дату(new); если дата существующая в БД, то формируем для неё шорткод на основе id, а если дата новая, то формируем вместо шорткода соответствующее сообщение(messages["shortCode"])
		dataSlice['id']+="";
		if (dataSlice['id'].match(/^new/)==null){
			shortCode="[autodate id='"+dataSlice['id']+"']";
		}
		else {
			shortCode=messages["shortCode"];
		}
		// Добавляем в строку ячейки, с элементами формы, необходимыми для редактирования дат
		$(tableRow)
			.append("<td><label><input type='radio' name='is_running_radio_"+dataSlice['id']+"' value='enb'"+dataSlice['enbFlag']+">"+messages["flagEnabled"]+"</label><br><label><input type='radio' name='is_running_radio_"+dataSlice['id']+"' value='dsb'"+dataSlice['dsbFlag']+">"+messages["flagDisabled"]+"</label></td>")
			.append("<td class='short_code_cell'>"+shortCode+"</td>")
			.append("<td><input type='text' class='regular_text' name='short_desc_"+dataSlice['id']+"' value='"+dataSlice['shortDesc']+"'></td>")
			.append("<td><input type='date' name='target_date_"+dataSlice['id']+"' value='"+dataSlice['tdValue']+"'></td>")
			.append("<td><input name='off_target_"+dataSlice['id']+"' type='number' step='1' min='1' value='"+dataSlice['toIntl']+"' class='small-text'></td>")
			
			.append("<td><input type='date' name='upd_date_"+dataSlice['id']+"' value='"+dataSlice['udValue']+"'></td>")
			.append("<td><input name='int_per_update_"+dataSlice['id']+"' type='number' step='1' min='1' value='"+dataSlice['uiIntl']+"' class='small-text'></td>")
			
			.append("<td><span class='button button_primary save_interval_change' data-target-id='"+dataSlice['id']+"'>"+messages['saveButton']+"</span></td>")
			.append("<td><span class='button button_primary delete_interval_button' data-target-id='"+dataSlice['id']+"' title='"+messages['deleteButtonTip']+"'>X</span></td>")
	}
	
	// Объявляем функцию, которая будет прикреплять событие клика к каждой новой кнопке сохранения даты
	//		btn - кнопка сохранения, к которой необходимо прикрепить событие и обработчик
	function attachSaveButtonEvent(btn){
		$(btn).click(function(){
			let sp_class="special_wpdu_row_attention"; // специальный класс для строки таблицы, в которой находится только что сохранённая дата
			
			// Получаем значения из всех полей, соответствующих дате
			let uId=$(this).attr("data-target-id");
			let shortDesc=$("[name=short_desc_"+uId+"]").val();
			let updDate=$("[name=upd_date_"+uId+"]").val();
			let uiIntl=$("[name=int_per_update_"+uId+"]").val();
			let tdValue=$("[name=target_date_"+uId+"]").val();
			let toIntl=$("[name=off_target_"+uId+"]").val();
			let runningFlag=$("[name=is_running_radio_"+uId+"]:checked").val();
			
			// Формируем объект для запроса на основе значений, полученных из полей
			let outgoingData={
				action:"autodate_manager",
				id:uId,
				short_desc:shortDesc,
				update_date:updDate,
				update_interval:uiIntl,
				target_date:tdValue,
				target_offset:toIntl,
				is_running:runningFlag
			}
			// Находим строку, в которой находится дата, которую мы пытаемся сохранить и добавляем для неё спец. класс
			let parentRow=$(this).parentsUntil("tr").eq(-1).parent("tr");
			$(parentRow).addClass(sp_class);
			
			// Отправляем ajax-запрос на сервер методом post
			$.post( ajaxurl, outgoingData,
				// Определяем обработчик ответа от сервера (response - ответ от сервера)
				function( response ){
					// Разбиваем ответ на части по разделителю -|-
					let rqst_results=response.split("-|-");
					
					// Если первая часть ответа сервера говорит о том, что была сохранена новая дата(не none и не old)
					if (rqst_results[0]!="none" && rqst_results[0]!="old"){
						// Обходим все input'ы в строке, и заменяем "временную" часть имен этих полей на id, переданный в ответе
						$(parentRow).find("input").each(function(){
							let nameVal=$(this).attr("name");
							nameVal=nameVal.replace(/new[0-9]+/,rqst_results[0]);
							$(this).attr("name",nameVal);
						});
						// Формируем шорткод на основе того же id и выводим его в отведённой для этого ячейке (.short_code_cell)
						let short_code='[autodate id="'+rqst_results[0]+'"]';
						$(parentRow).find("td.short_code_cell").text(short_code);
						// Добавляем атрибут data-target-id со значением ввиде полученного id для кнопки удаления даты, а также для самой строки, в которой содержится дата
						$(parentRow).find(".delete_interval_button").attr("data-target-id",rqst_results[0]);
						$(btn).attr("data-target-id",rqst_results[0]);
					}
					// Выводим часть ответа от сервера, отвечающую за оповещение о результате запроса
					alert( rqst_results[1] );
					// Через 5 секунд удаляем спец. класс, подсвечивающий строку в которой были осуществлены изменения даты
					setTimeout(function(){ $(parentRow).removeClass(sp_class); },5000);
				}
			);
		});
	}
	
	// Объявляем функцию, которая будет прикреплять событие клика к каждой новой кнопке удаления даты
	//		btn - кнопка удаления, к которой необходимо прикрепить событие и обработчик
	function attachDeleteButtonEvent(btn){
		$(btn).click(function(){
			let sp_class="special_wpdu_row_attention"; // специальный класс для строки таблицы, в которой находится удаляемая дата
			// Находим строку, в которой находится дата, которую мы пытаемся удалить и добавляем для неё спец. класс
			let parentRow=$(this).parentsUntil("tr").eq(-1).parent("tr");
			$(parentRow).addClass(sp_class);
			
			// Уточняем точно ли пользователь хочет удалить эту дату, если нет - прерываем выполнение функции
			let answer=confirm(messages["confirmQuestion"]);
			if (answer===false){
				return false;
			}
			
			// Получаем id удаляемой даты
			let uId=$(this).attr("data-target-id"); 
			
			// Уточняем, что полученный id не указывает на только что созданную, но ещё не сохранённую дату
			if (uId.match(/^new/)==null){
				// Формируем объект данных отправляемых в ajax-запросе
				let outgoingData={
					action:"autodate_delete_manager",
					id:uId
				};
				// Отправляем ajax-запрос методом post и обрабатываем получение ответа
				$.post( ajaxurl, outgoingData,
					// обрабатываем получение ответа (response) на запрос
					function( response ){
						// Если в ответе на запрос указано, что были переданы некорректные данные ИЛИ запрос был "холостым"(не привёл к удалению данных), выводим сообщение об ошибке в процессе удаления(messages["deleteFailMsg"]) и удаляем через 5 сек спец. класс, указывающий на строку в которой производилось изменение
						if (response=="data corrupted" || response=="idle request"){
							alert(messages["deleteFailMsg"]);
							setTimeout(function(){ $(parentRow).removeClass(sp_class); },5000);
						}
						// Если ответ говорит о том, что данные были удалены, то выводим соответствующее сообщение и удаляем эту строку
						else {
							alert(messages["deleteSuccessMsg"]);
							$(parentRow).remove();
						}
					}
				);
			}
			// Если речь идёт о ещё не сохранённой дате, то просто удаляем строку
			else {
				$(parentRow).remove();	
				alert(messages["deleteSuccessMsg"]);
			}
			
		});
	}
	
	// Формируем html-код редактора дат, основанный на данных переданных из php(в переменной dateListData)
	for (a=0;a<dateListData.length;a++){
		// Добавляем в таблицу редактора пустую строку
		$("#date_intervals_list").append("<tr class='dates_list_row'></tr>");
		// Определяем целевую строку, которую будем наполнять ячейками
		let targetRow=$("#date_intervals_list").find("tr.dates_list_row:last-child");
		// Добавляем в строку html-код на основе текущего элемента списка данных по датам
		createListRow(targetRow,dateListData[a]);
		
		// Определяем кнопку сохранения в созданной строке
		let saveButton=$("#date_intervals_list").find("tr.dates_list_row:last-child").find(".save_interval_change");
		// Прикрепляем к ней действия по сохранению даты
		attachSaveButtonEvent(saveButton);
		// Определяем кнопку удаления в созданной строке
		let deleteButton=$("#date_intervals_list").find("tr.dates_list_row:last-child").find(".delete_interval_button");
		// Прикрепляем к ней действия по удалению даты
		attachDeleteButtonEvent(deleteButton);
	}
	
	// Описываем обработчика клика по кнопке добавления новой даты
	$("#add_interval_date").click(function(){
		// Уточняем точно ли пользователь хочет добавить новую дату, если нет - прерываем функцию
		let answer=confirm(messages["questionForAdd"]);
		if (answer===false){
			return false;
		}
		
		// Получаем значение атрибута, отвечающего за подсчёт добавленных дат
		let counter=$(this).attr("data-new-counter");
		// Формируем временный id для элементов редактора только что созданной даты
		let tempID="new"+counter;
		// Приводим значение "счётчика" к числовому, увеличиваем на 1 и меняем на него значение атрибута data-new-counter
		counter=parseInt(counter);
		counter++;
		$(this).attr("data-new-counter",counter);
		
		// Добавляем пустую строку в конец таблицы редактора и определяем её как целевую, куда будут добавляться ячейки редактора
		$("#date_intervals_list").append("<tr class='dates_list_row'></tr>");
		let targetRow=$("#date_intervals_list").find("tr.dates_list_row:last-child");
		
		// Формируем данные, на основе которых сформируем ячейки редактора в новой строке
		let rowElemsOptions={
			id:tempID,
			shortDesc:"",
			enbFlag:"",
			dsbFlag:"checked='checked'",
			udValue:currentDate,
			uiIntl:1,
			tdValue:currentDate,
			toIntl:1
		}
		
		// Наполняем строку на основе сформированных данных
		createListRow(targetRow,rowElemsOptions);
		// Определяем кнопку сохранения в созданной строке
		let saveButton=$("#date_intervals_list").find("tr.dates_list_row:last-child").find(".save_interval_change");
		// Прикрепляем к ней действия по сохранению даты
		attachSaveButtonEvent(saveButton);	
		
		// Определяем кнопку удаления в созданной строке
		let deleteButton=$("#date_intervals_list").find("tr.dates_list_row:last-child").find(".delete_interval_button");
		// Прикрепляем к ней действия по удалению даты
		attachDeleteButtonEvent(deleteButton);
	});
	
	// Обработчик отображения/скрытия подсказок по работе с плагином
	$(".sidebar-name").click(function(){
		$(this).parentsUntil(".widgets-holder-wrap").eq(0).parent(".widgets-holder-wrap").toggleClass("closed");
	});
});