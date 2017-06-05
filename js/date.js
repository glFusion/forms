/**
*   Updates the 3-part date field with data from the datepicker.
*   @param  Date    d       Date object
*   @param  string  fld     Field Name
*   @param  integer tm_type 12- or 24-hour indicator, 0 = no time field
*/
function FRM_updateDate(d, fld, tm_type)
{
    document.getElementById(fld + "_month").selectedIndex = d.getMonth() + 1;
    document.getElementById(fld + "_day").selectedIndex = d.getDate();
    document.getElementById(fld + "_year").value = d.getFullYear();

    // Update the time, if time fields are present.
    if (tm_type != 0) {
        var hour = d.getHours();
        var ampm = 0;
        if (tm_type == "12") {
            if (hour == 0) {
                hour = 12;
            } else if (hour > 12) {
                hour -= 12;
                ampm = 1;
            }     
            document.getElementById(fld + "_ampm").selectedIndex = ampm;
        }
        document.getElementById(fld + "_hour").selectedIndex = hour;
        document.getElementById(fld + "_minute").selectedIndex = d.getMinutes();
    }
}

