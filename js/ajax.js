/**
*   Save a field from a defined AJAX-type form
*
*   @param  string  frm_id  Form ID
*   @param  integer fld_id  Field ID
*   @param  string  elem    Document element ID, for updating the view
*/
var FORMS_ajaxSave = function(frm_id, fld_id, elem) {
    var var_type = elem.type;
    var fld_value;

    switch (var_type) {
    case "checkbox":
    case "radio":
        var elem_stat = elem.checked;
        fld_set = elem.checked ? true : false;
        break;
    case "select-one":
        var elem_stat = elem.selected;
        //fld_set = elem.selected ? true : false;
        fld_set = elem.value;
        break;
    case "text":
        var elem_stat = "";
        fld_set = elem.value;
    }

    var dataS = {
        "frm_id" : frm_id,
        "fld_id" : fld_id,
        "elem_id" : elem.id,
        "fld_set" : fld_set,
        "fld_type": elem.type,
        "fld_value": elem.value,
        "action" : "ajax_fld_post",
    };
    data = $.param(dataS);
    $.ajax({
        type: "POST",
        dataType: "json",
        url: glfusionSiteUrl + "/forms/ajax.php",
        data: data,
        success: function(result, textStatus, jqXHR) {
            try {
                if (result.status == 0) {
                    if (elem_stat != "") {
                        elem_stat = elem_stat ? false : true;   // toggle checkbox
                    }
                    $.UIkit.notify(result.msg, {timeout: 1000,pos:'top-center'});
                } else {
                    $.UIkit.notify(result.msg, {timeout: 1000,pos:'top-center'});
                }
            }
            catch(err) {
                $.UIkit.notify(result.msg, {timeout: 1000,pos:'top-center'});
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
    });
    return false;
};

/**
*   Save a field from an autotag
*
*   @param  integer fld_name    Field name, to create session var
*   @param  string  elem        Document element ID, for updating the view
*/
var FORMS_autotagSave = function(fld_name, elem) {
    var elem_stat = elem.checked;
    switch (elem.type) {
    case "checkbox":
        var fld_value = elem.checked ? elem.value : 0;
        break;
    case "radio":
        var fld_value = elem.value;
        var fld_set = elem.checked ? true : false;
        break;
    }
    var dataS = {
        "elem_id" : elem.id,
        "fld_type" : elem.type,
        "fld_name" : fld_name,
        "fld_set" : fld_set,
        "fld_value" : fld_value,
        "action" : "ajax_autotag_post",
    };
    data = $.param(dataS);
    $.ajax({
        type: "POST",
        dataType: "json",
        url: glfusionSiteUrl + "/forms/ajax.php",
        data: data,
        success: function(result, textStatus, jqXHR) {
            try {
                if (result.status == 0) {
                    elem_stat = elem_stat ? false : true;   // toggle checkbox
                    $.UIkit.notify(result.msg, {timeout: 1000,pos:'top-center'});
                } else {
                    $.UIkit.notify(result.msg, {timeout: 1000,pos:'top-center'});
                }
            }
            catch(err) {
                $.UIkit.notify(result.msg, {timeout: 1000,pos:'top-center'});
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        },
    });
    return false;
};
