jQuery(document).ready(function($){
       
        options = {
        serviceUrl: t3_suggest_url,
            minChars:2,
            delimiter: /(,|;)\s*/, // regex or character
            maxHeight:400,
            width:600,
            zIndex: 9999,
            dataType: 'jsonp',
             deferRequestBy: 0, //miliseconds
             params: { tematres_uri: $("#tematres_uri").val() }, //aditional parameters            
            deferRequestBy: 0, //miliseconds
            noCache: false, //default is false, set to true to disable caching
            // callback function:
            onSelect:  function() { 
                $("#t3_searchform").submit(); }
                };
       $('#t3_search_input').autocomplete(options);
//   }
}); 
