 /*  Updates submission form fields based on changes in the category
 *  dropdown.
 */
var FRMxmlHttp;

function FRMtoggleEnabled(ck, id, type, v, base_url)
{
  if (ck.checked) {
    newval=1;
  } else {
    newval=0;
  }

  FRMxmlHttp=FRMGetXmlHttpObject();
  if (FRMxmlHttp==null) {
    alert ("Browser does not support HTTP Request")
    return
  }
  var url=base_url + "/ajax.php?action=toggleEnabled";
  url=url+"&id="+id;
  url=url+"&type="+type;
  url=url+"&var="+v;
  url=url+"&newval="+newval;
  url=url+"&sid="+Math.random();
  FRMxmlHttp.onreadystatechange=FRMsc_toggleEnabled;
  FRMxmlHttp.open("GET",url,true);
  FRMxmlHttp.send(null);
}

function FRMsc_toggleEnabled()
{
  var newstate;

  if (FRMxmlHttp.readyState==4 || FRMxmlHttp.readyState=="complete")
  {
    xmlDoc=FRMxmlHttp.responseXML;
    id = xmlDoc.getElementsByTagName("id")[0].childNodes[0].nodeValue;
    //imgurl = xmlDoc.getElementsByTagName("imgurl")[0].childNodes[0].nodeValue;
    baseurl = xmlDoc.getElementsByTagName("baseurl")[0].childNodes[0].nodeValue;
    type = xmlDoc.getElementsByTagName("type")[0].childNodes[0].nodeValue;
    if (xmlDoc.getElementsByTagName("newval")[0].childNodes[0].nodeValue == 1) {
        checked = "checked";
        newval = 0;
    } else {
        checked = "";
        newval = 1;
    }
    document.getElementsByName(type+"_"+id).checked = checked;
    
  }

}

function FRMGetXmlHttpObject()
{
  var objXMLHttp=null
  if (window.XMLHttpRequest)
  {
    objXMLHttp=new XMLHttpRequest()
  }
  else if (window.ActiveXObject)
  {
    objXMLHttp=new ActiveXObject("Microsoft.XMLHTTP")
  }
  return objXMLHttp
}

