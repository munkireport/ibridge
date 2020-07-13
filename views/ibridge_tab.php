<div id="ibridge-tab"></div>
<h2 data-i18n="ibridge.clienttab"></h2>

<script>
$(document).on('appReady', function(){
    // Set the tab badge to blank
    $('#ibridge-cnt').html("");
    
	$.getJSON(appUrl + '/module/ibridge/get_data/' + serialNumber, function(data){
        
        if( data.length == 0 ){
            $('#ibridge-tab').html('<div id="ibridge-tab"></div><h2 data-i18n="ibridge.clienttabtitle"></h2><h4><i class="fa fa-link"></i> '+i18n.t('ibridge.noibridge')+"</h4>");
        } else{ 
        
            var skipThese = ['id','serial_number'];
            $.each(data, function(i,d){
                // Generate rows from data
                var rows = ''
                for (var prop in d){
                    // Skip skipThese
                    if(skipThese.indexOf(prop) == -1){
                        if (d[prop] == '' || d[prop] == null || prop == 'model_name'){
                            // Do nothing for empty values to blank them
                        } else if (prop == "marketing_name"){
                            // Set the tab badge
                            $('#ibridge-cnt').text(d[prop])

                        } else if((prop == 'apple_internal') && d[prop] == 1){
                           rows = rows + '<tr><th>'+i18n.t('ibridge.'+prop)+'</th><td><span class="label label-danger">'+i18n.t('yes')+'</span></td></tr>';
                        } else if((prop == 'apple_internal') && d[prop] == 0){
                           rows = rows + '<tr><th>'+i18n.t('ibridge.'+prop)+'</th><td>'+i18n.t('no')+'</td></tr>';

                        } else {
                            rows = rows + '<tr><th>'+i18n.t('ibridge.'+prop)+'</th><td>'+d[prop]+'</td></tr>';
                        }
                    }
                }
                $('#ibridge-tab')
                    .append($('<h4>')
                        .append($('<i>')
                            .addClass('fa fa-link'))
                        .append(' '+d.model_name))
                    .append($('<div style="max-width:475px;">')
                        .append($('<table>')
                            .addClass('table table-striped table-condensed')
                            .append($('<tbody>')
                                .append(rows))))    
            })
        }
	});
});
</script>
