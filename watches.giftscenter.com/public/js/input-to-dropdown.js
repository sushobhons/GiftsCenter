function inputsToDropdown(container, defaultValue, inputType, controlContainer, hideContainer){
    /**
     * @param {string}       [container='.parent']              - A parent node of a group with inputs
     * @param {string}       [defaultValue='Select please']     - A deafault name of your dropdown button
     * @param {string}       [inputType='checkbox or radio']    - A type of a group with inputs
     * @param {string}       [controlContainer='.parent']       - A node where you put your new dropdown
     * @param {string}       [hideContainer='.parent > table']  - A group with inputs that you want to hide (Can be empty)
     *
     * Usage:
     *
     * Example #1 (input[type='checkbox']):
        <div class="checkbox-group">
            <table>
                <tr>
                    <td><input id="value1" type="checkbox"><label for="value1">Value 1</label></td>
                    <td><input id="value2" type="checkbox"><label for="value2">Value 2</label></td>
                    <td><input id="value3" type="checkbox"><label for="value3">Value 3</label></td>
                    <td><input id="value4" type="checkbox"><label for="value4">Value 4</label></td>
                </tr>
            </table>
        </div>
        <script>inputsToDropdown('.checkbox-group', 'Select please', 'checkbox', '.checkbox-group', '.checkbox-group > table');</script>
      *
      *  
      * Example #2 (input[type='radio']):
        <div class="radio-group">
            <div><label><input name="radio1" type="radio">Value 1</label></div>
            <div><label><input name="radio1" type="radio">Value 2</label></div>
            <div><label><input name="radio1" checked="radio" type="radio"> Value 3</label></div>
            <div><label><input name="radio1" type="radio">Value 4</label></div>
        </div>
        <script>inputsToDropdown('.radio-group', 'Select please', 'radio', '.radio-group', '');</script>
     */
    if ($(container).length > 0 && ($(container).find('input[type="checkbox"]').length > 0 || $(container).find('input[type="radio"]').length > 0)) {
        var btnGroup 			= 	$('<div>', {class: 'btn-group btn-group__multiple btn-group__multiple-' + inputType}),
            btn 				= 	$('<button>', {
                'class': 			'btn btn-default dropdown-toggle btn-action btn-type__' + inputType,
                'data-toggle': 		'dropdown',
                'aria-haspopup':    'true',
                'aria-expanded':    'true',
                'html': 			'<span class="value">' + defaultValue + '</span><span class="num"></span><span class="btn-placeholder"></span> <span class="caret"></span>'
            }),
            control 			= 	$(container).find('input[type="' + inputType + '"]').parent(),
            controlContainer	= 	$(controlContainer),
            hideContainer		= 	$(hideContainer), 
            list 				= 	$('<ul>', {'class': 'dropdown-menu'}),
            placeholder 		= 	btn.find('.value'),
            btnAction 			=	btn.find('.btn-action'),
            placeholderHtml		=	btn.find('.btn-placeholder'),
            num					=	btn.find('.num').hide();


       function getSelected(elements) {
       		if (inputType == 'checkbox') {
           		var widthInner = 0,
           			widthOuter = btn.width();
           		$.each(elements, function() {
        	 		widthInner += $(this).find('label').width() + 20;
        		});
        		num.text('Selected ' + elements.length);
        		if (widthInner + 20 > widthOuter) {
        			num.show();
        			placeholderHtml.hide();
        		} else {
        			num.hide();
        			placeholderHtml.show();
        		}
        		markLastChild($(placeholderHtml));
        		return elements.length;
    		}
       } 

       function renderButton() {
       		if (inputType == 'checkbox') {
       			btnGroup.find('li.list-item.active').length != 0 ? placeholder.addClass('hide') : placeholder.removeClass('hide');
       		}
       }

        function markLastChild(parent){
        	$(parent).find('span[data-value]').removeClass('last-visible');
        	$(parent).find('span[data-value]:visible:last').addClass('last-visible');
        }    

        function markAll(list) {
        	var li 			= 	$('<li>', {'html': '<a><input type="checkbox"><label>Select all</label></a>', 'class': 'select-all'}),
        		selectedAll = false;
        	list.prepend(li);   
        	li.on('click', function(){
        		selectedAll ? selectedAll = false : selectedAll = true; 
        		$(this).find('input').prop("checked", !$(this).find('input').prop("checked"));
        		$(list).find('li.active').length == $(list).find('li').length ? selectedAll = false : selectedAll = true;

        		if (selectedAll) {
        			$(this).closest('ul').find('li').addClass('active');
            		$(list).find('input').prop('checked', true);
            		$.each($(this).closest('.btn-group').find('span[data-value]'), function() {
                    	$(this).removeClass('hide');
                    });	       
        		} else {     
            		$(this).closest('ul').find('li').removeClass('active');
            		$(list).find('input').prop('checked', false);
            		$.each($(this).closest('.btn-group').find('span[data-value]'), function() {
                    	$(this).addClass('hide');
                    });
        		} 
        		getSelected($(this).closest('.btn-group').find('li.list-item.active'));
        		renderButton();
        		return false;           		
        	});
        }

        function renderDropdown(inputType){
            btnGroup.append(btn);
            controlContainer.append(btnGroup);
            $.each(control, function(){
                var li 		= 	$('<li>', {'title': '' || $(this).attr('title'), 'class': 'list-item'}),
                    input 	= 	$(this).find('input'),
                    label 	= 	$(this).find('label').length > 0 ? $(this).find('label') : $(this).parent().find('label'),
                    text	=	label.text(),
                    span 	=	$('<span>', { 'html': text, 'data-value': text.replace(/\s/g, ''), 'class': 'placeholder-item hide' });
                if (input.prop('checked') == true) li.addClass('active');
                var a = $('<a>').append(input,label);
                li.append(a);
                list.append(li);
                btnGroup.append(list);
                handleClick(li, list);
                if (inputType == 'checkbox') placeholderHtml.append(span);
                if (inputType == 'checkbox' && input.prop('checked')) span.removeClass('hide');                  
                if (inputType == 'radio' && input.prop('checked')) placeholder.text(label.text());                   
            });
            if ($(hideContainer).length > 0) $(hideContainer).hide();
            if (inputType == 'checkbox') markAll($(list));
            markLastChild(placeholderHtml);
            btnAction.append(placeholderHtml);
            list.removeClass('dropdown-menu');  
            getSelected(btnGroup.find('li.list-item.active'));
            list.addClass('dropdown-menu');  
            renderButton();
        }
        renderDropdown(inputType);

        function handleClick(element, parentList){
            element.on('click', 'a', function(){
                var input 	= 	element.find('input'),
                    prop 	= 	input.prop('checked'),
                    link	=	$(this);
                	list 	= 	parentList,
                	grpLoc  =	$(this).closest('.btn-group');
                if (inputType == 'checkbox') {
                    if (prop == true){
                        prop = false;
                        link.parent('li').removeClass('active');
                        $(this).closest('ul').find('.select-all').removeClass('active');
                        $(this).closest('ul').find('.select-all input').prop('checked', false);
                        $.each($(this).closest('.btn-group').find('span'), function() {
                        	if (link.find('label').text().replace(/\s/g, "") === $(this).data('value')) $(this).addClass('hide');
                        });
                    } else {
                        prop = true;
                        link.parent('li').addClass('active');
                        $.each($(this).closest('.btn-group').find('span'), function() {
                        	if (link.find('label').text().replace(/\s/g, "") === $(this).data('value')) $(this).removeClass('hide');
                        })
                    }
                    input.prop('checked', prop);
                    markLastChild($(this).closest('.btn-group'));
                    $(this).closest('.btn-group').find('.placeholder-item:visible').length != 0 ? placeholder.addClass('hide') : placeholder.removeClass('hide');
                    getSelected($(this).closest('.btn-group').find('li.list-item.active'))
                }
                if (inputType == 'radio') {
                    list.find('input').prop('checked', false);
                    list.find('li').removeClass('active');
                    link.parent('li').addClass('active');
                    link.find(input).prop('checked', true);
                    list.parent('.btn-group').removeClass('open');
                    placeholder.text($(this).find('label').text())
                }
                renderButton();
                return false;
            });
        }
    }
}
