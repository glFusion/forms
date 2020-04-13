/**
 * Updates the 3-part date field with data from the datepicker.
 *
 * @param  string  dtstr   Date string (YYYY-MM-DD)
 * @param  string  fld     Field base name
 */
function FRM_updateDate(dtstr, fld)
{
    var d = new Date(dtstr);
    document.getElementById(fld + "_month").selectedIndex = d.getMonth() + 1;
    document.getElementById(fld + "_day").selectedIndex = d.getDate() + 1;
    document.getElementById(fld + "_year").value = d.getFullYear();
}
