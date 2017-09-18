/* File: plugins/JbrBackup/JbrBackup.js */

jQuery(document).ready(function(){
    
    // === Functions ===

    function _flashResponse(elemId, msg) {
        jQuery(elemId)
            .html(msg)
            .show()
            .delay(2000)
            .hide(200);
    }
    
    function _showResponse(elemId, msg) {
        jQuery(elemId)
            .html(msg);
    }
    
    function _myPost(targetElem, data) {
        var respElemId, respFunc;
        respElemId = jQuery(targetElem).attr('data-flash-response');
        if (respElemId) {
            respFunc = _flashResponse;
        } else {
            respElemId = jQuery(targetElem).attr('data-show-response');
            respFunc = _showResponse;
        }
        jQuery.post(
            ajaxurl,
            data,
            function(response){
                respFunc(respElemId, response);
            }
        );
    }
    
    // === Register Handlers ===
    
    jQuery('.jbr-bu-form').submit(function(event){
        event.preventDefault();
        var data = jQuery(this).serialize();
        data += '&action=jbrbu_ajax_event';
        _myPost(this, data);
    });
    
    jQuery('.jbr-bu-button').click(function(event){
        var data = {
            'action': 'jbrbu_ajax_event',
            'cmd': jQuery(this).attr('data-cmd')
        }
        _myPost(this, data);
    });
    
});








