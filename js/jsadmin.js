(function($) {
  
  function ucwords(str) { 
    return (str + '')
      .replace(/^(.)|\s+(.)/g, function ($1) {
        return $1.toUpperCase()
      })
  }


  $('document').ready(function(){  
  
  $('#simpleq_set_choices, #simpleq_questionnaire_group, #simpleq_questionnaire_group_child').tagsInput({defaultText: 'Add', width: '474px'});        
  
  $('#simpleq_questionnaire .form-control, #simpleq_questionnaire #set_choices_tag').blur(function(){
  var q=$('textarea[name="question"]').val(),c=$('#set_choices').val(),inp=$('select[name="input"]').val(),sel='',r=0,ch='',opt='';
  
  if (c) {
    ch = c.split(',');
    for( r in ch ) {
       if ( inp == 'checkbox' || inp == 'radio' ) { 
         sel +=  '<li><input type="'+ inp +'" name="temp_choices[]" value="'+ ucwords(ch[r]) +'"/>&nbsp;<b>' + ucwords(ch[r]) + '</b></li>';
       }
       //input with option
       if ( inp == 'checkbox_option' || inp == 'radio_option' ) {
         opt = ( inp == 'checkbox_option' ) ? 'checkbox' : 'radio'  
         sel +=  '<li><input type="'+ opt +'" name="temp_choices[]" value="'+ ucwords(ch[r]) +'"/>&nbsp;<b>' + ucwords(ch[r]) + '</b></li>';
       }       
       
       if ( inp == 'select' ) {
         sel += '<option value="'+ ucwords(ch[r]) +'">'+ ucwords(ch[r]) +'</option>';  
       }
       
    }
  }
  
  if ( inp == 'textarea' )
  sel =  '<textarea name="temp_choices[]" cols="80" rows="4"></textarea>';
  
  if ( inp == 'select' )
  sel = '<li><select>'+ sel +'</select></li>';
  
  if (opt) {
    sel += '<li><input type="text" name="temp_choices[option]" placeholder="Other (please state)"/></li>';
  }
  
  //define
  sel = '<ul class="simpleq-choices">'+ sel +'</ul>';
  
  $('.qpreview .prev-question').html(q);    
  $('.qpreview .prev-add-info').html( $('textarea[name="add_info"]').val().replace(/\r?\n/g, '<br />') );  
  $('.qpreview .prev-choices').html(sel);
  
  });
  
  //Action delete
  $('#doaction').click(function(){
  
  if ($('#bulk-action-selector-top').val()=='delete') {
    if (!confirm('Are you sure, you want delete ?')) {
       return false;
    }
  }
  
  });
  
  //Questionnaire page
  if ( typeof $('.simpleq #questionnaire')[0] !== 'undefined' ) {
    $('#the-list input[data-show="0"]').parent().parent().addClass('qa-hide');
    
    $('.simpleq #questionnaire textarea[name="question"]').blur();
  }

  
  //check a notice to hide
  if ( typeof $('.simpleq .notice.updated')[0] !== 'undefined' ) {
    setTimeout(function(){ $('.simpleq .notice.updated').fadeOut(); },3000);
  }
  
  });  
	
})(jQuery);

// URL encode plugin
jQuery.extend({URLEncode:function(c){var o='';var x=0;c=c.toString();var r=/(^[a-zA-Z0-9_.]*)/;
  while(x<c.length){var m=r.exec(c.substr(x));
    if(m!=null && m.length>1 && m[1]!=''){o+=m[1];x+=m[1].length;
    }else{if(c[x]==' ')o+='+';else{var d=c.charCodeAt(x);var h=d.toString(16);
    o+='%'+(h.length<2?'0':'')+h.toUpperCase();}x++;}}return o;}
});
