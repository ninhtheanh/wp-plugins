function save_custom_table()
{              
    //console.log(custom_table_ajax.ajaxurl);
    jQuery.ajax({
        url: custom_table_ajax.ajaxurl,
        type: 'post',
        data: { action: "save_custom_table", id: 13,  name : 'The Anh', email: 'anhninh@mail.com', age: 37 },
        beforeSend: function(){
            
        },
        success: function( result ) {                    
            console.log(result);
        },
        complete: function(){      
            
        }
    });    
}

// jQuery(document).ready(function ($) {
//     $( "#btn_save_person" ).click(function() {
//         console.log("click");  
//         console.log(custom_table_ajax.ajaxurl); 
//         save_custom_table();
//     });
// });
