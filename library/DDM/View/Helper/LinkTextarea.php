<?php

class DDM_View_Helper_LinkTextarea extends Zend_View_Helper_FormTextarea {

    public function linkTextarea($name, $value = null, $attribs = null) {
        $info = $this->_getInfo($name, $value, $attribs);
        $id = preg_replace('/[^a-zA-Z0-9]/', '', $info['id']);
        $functionName = "add".$id."Hyperlink()";
        $html = '
            <div><button id="'.$info['id'].'-link" type="button" title="Add Hyperlink" onclick="'.$functionName.'" disabled><i class="fa fa-link"></i></button></div>
        ';
        $js = '
            <script type="text/javascript">
                $(document).ready(function(){
                    $("#'.$info['id'].'")
                        .select(function(){
                            $("#'.$info['id'].'-link").prop("disabled", false);
                            $("#'.$info['id'].'").data("start", $("#'.$info['id'].'")[0].selectionStart);
                            $("#'.$info['id'].'").data("end", $("#'.$info['id'].'")[0].selectionEnd);
                        })
                        .on("blur focus keydown mousedown", function(){
                            if($("#'.$info['id'].'").data("entered") == false) {
                                $("#'.$info['id'].'-link").prop("disabled", true);
                                $("#'.$info['id'].'").data("start", null);
                                $("#'.$info['id'].'").data("end", null);
                            } else if($("#'.$info['id'].'").data("start") == null || $("#'.$info['id'].'").data("start") == null){
                                $("#'.$info['id'].'-link").prop("disabled", true);
                            }
                        })
                        .data("entered", false)
                        .data("start", null)
                        .data("end", null);
                    $("#'.$info['id'].'-link")
                        .mouseenter(function(){
                            $("#'.$info['id'].'").data("entered", true);
                        })
                        .mouseleave(function(){
                            $("#'.$info['id'].'").data("entered", false);
                        });
                });
                function '.$functionName.'{
                    $("#'.$info['id'].'").data("entered", false);
                    $("#'.$info['id'].'-link").prop("disabled", true);
                    var start = $("#'.$info['id'].'").data("start");
                    var end = $("#'.$info['id'].'").data("end");
                    var originalText = $("#'.$info['id'].'").val();
                    var originalSubstring = originalText.substring(start, end);
                    var beforeSubstring = originalText.substring(0, start);
                    var afterSubstring = originalText.substring(end);
                    var hyperlink = prompt("Enter the Hyperlink for \'" + originalSubstring + "\'", "");
                    if(hyperlink) {
                        hyperlink = hyperlink.trim();
                        if(hyperlink) {
                            var hyperlinkLower = hyperlink.toLowerCase();
                            if(hyperlinkLower.substr(0, 4) != "http") {
                                hyperlink = "http://" + hyperlink;
                            }
                            var newSubstring = "<a href=\'" + hyperlink + "\' target=\'_blank\'>" + originalSubstring + "</a>";
                            var newText = beforeSubstring + newSubstring + afterSubstring;
                            $("#'.$info['id'].'").val(newText);
                        }
                    }
                    if(newSubstring) {
                        $("#'.$info['id'].'")[0].selectionEnd = start + newSubstring.length;
                        $("#'.$info['id'].'").focus();
                    }
                    $("#'.$info['id'].'").data("start", null);
                    $("#'.$info['id'].'").data("end", null);
                }
            </script>
        ';
        return $html.parent::formTextarea($name, $value, $attribs).$js;
    }
}