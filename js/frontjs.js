(function($)
{
  window.gcaptcha = function() {
    alert('load');
  } 


  $('document').ready(function(){ 
    var qa_form = $('#accumed-qa-form'), qa_c = parseInt(qa_form.attr('data-qa-count'));
    
    //option switch
    $('.accumed_qa_option').each(function(i,e){
    
    $(e).change(function(){
    var id=$(this).attr('data-id');
    
    if ( this.value=='Yes' ) {
      $('.qa_c'+ id).fadeIn();
      } else {
      $('.qa_c'+ id).fadeOut();
    }
    
    });
    
    });
    
        
    //Testing
    $('.accumed_qa_next').each(function(i,e){
    
    $(e).click(function(){
    var r=$(this).attr('data-qa-next'),n=parseInt(r)+1;
    
    $('.qa' + r).hide();
    $('.qa' + n).fadeIn();
        
    if ( n == qa_c ) {
      qa_form.submit();
    }
        
    return false;    
    });
    
    });
    
    //TODO: To make all validation in one form in different slide
    
    $('.accumed-live-form').each(function(i,e){
    var n=$(e).attr('data-next'),p=$(e).attr('data-prev');
    
    if ( n=='final' ) {   
    
    
    $(e).orpValidator({singleError:true}).submit(function(e){    
      if (!e.isDefaultPrevented()) {
      
        if ( grecaptcha.getResponse() ) {
          accumedPost['form6'] = $(e.target).serializeArray();
          $('#accumed_forms').val(JSON.stringify(accumedPost));
          } else {
          $('.invalid-captcha').fadeIn();
          return false;
        }                
            
      }
    });    
    
    } else {
    
    $(e).orpValidator({singleError:true}).submit(function(e){
        
      if ( !e.isDefaultPrevented() ) {
      
        accumedPost['form' + p] = $(e.target).serializeArray();
            
        $('.stab'+p).addClass('non-active');
        $('.stab'+n).removeClass('non-active');
      
        $('.stab'+p+'-content').hide();
        $('.stab'+n+'-content').show();
        
        if ( n == 6 ) {
        setTimeout(function(){        
          $('.myname').html( $('input[name="first_name"]').val() + ' ' + $('input[name="last_name"]').val() );
        
          var wrapper = document.getElementById("signature-pad"), canvas = wrapper.querySelector("canvas");
          signaturePad = new SignaturePad(canvas, {onEnd: function(){          
          $('#e_signature').val( signaturePad.toDataURL() );          
          }});
        
        },100);
        }        
        
        
        e.preventDefault();
            
      }
      
            
    });
    
    }
    
    });
    
    //show popup
    (typeof $('#thankPopup')[0] !== 'undefined' && $('#thankPopup').modal());    
      
  });  
	
})(jQuery);