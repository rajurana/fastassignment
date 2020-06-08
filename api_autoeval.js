var getUrlParameter = function getUrlParameter(sParam) {
    var sPageURL = window.location.search.substring(1),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
};

$(document).ready(function () {
    
    let courseMod = getUrlParameter('id');
    let studentId = getUrlParameter('userid');
    if(!!courseMod && !!studentId) {
        $.ajax({
            url: "ajax_handler.php",
            data: {
                teacherView: 1,
                courseModule: courseMod,
                studentId: studentId
            },
            type: "GET",
            success: function(response) {
                response = JSON.parse(response, true);
            
                  const NODE_URL = "https://console.virtualwritingtutor.com/console";
                  const API_KEY = response.api_key;
                  const activity = response.activity;
                  
                    let testlink = response.test_links;
                    let test_name = response.test_name;
                    let category_name = response.category_name;
                    
                    let total_grammar_hits = response.grammar_hits;
                    let total_autoeval_student_hits = response.auto_eval_student;
                    let total_autoeval_teacher_hits = response.auto_eval_teacher;
                    
                    let used_grammar_hits = response.grammer_used_hits;
                    let autoeval_used_hits = response.autoeval_used_hits;
                    let dataToCheck = response.description;
                    if(dataToCheck !== null){
                        dataToCheck = dataToCheck.replace(/<br>/g,"\n");
                        dataToCheck = dataToCheck.replace(/<(?:br|\/div|\/p)>/g, "\n").replace(/<.*?>/g, "");
                        dataToCheck = dataToCheck.replace(/&nbsp;/g,''); 
                    }
                    
                    let teacherPermission = response.teacher_permission;
                    let isAdmin = response.admin;
                    
                    let remaining_grammar_hits = (total_grammar_hits - used_grammar_hits);
                    let remainingautoeval_hits = (total_autoeval_teacher_hits - autoeval_used_hits);
                    
                    if(teacherPermission === 1 && isAdmin === 0) {
                        $("#apimessage").html("No. of automated evaluations available: " + remainingautoeval_hits);
                    }
                    
                    
                    if(teacherPermission === 0 && isAdmin === 0) { $("#id_automatedevaluation").hide(); }
                
                  // Automated evaluation Async request
                  const automateEval = async (dataToCheck, testlink) => {
                    var myHeaders = new Headers();
                    myHeaders.append("vwtapikey", API_KEY);
                    myHeaders.append("Content-Type", "application/json");
                
                    var raw = JSON.stringify({
                      test_link: testlink,
                      text: dataToCheck,
                    });
                
                    var requestOptions = {
                      method: "POST",
                      headers: myHeaders,
                      body: raw,
                      redirect: "follow",
                    };
                
                    $("#loader").show();
                
                    const request = await (
                      await fetch(`${NODE_URL}/essay/test-feedback/v2`, requestOptions)
                    ).json();
                    
                    // Recording teacher API track
                    $.ajax({
                        url: "ajax_handler.php",
                        data: {
                            api_triggered_teacher: 1,
                            courseModule: courseMod,
                            grammar_hits: 0,
                            autoeval_hits: 1
                        },
                        type: "GET",
                        success: function(response) {
                            response = JSON.parse(response, true);
                
                            remaining_grammar_hits = response.remaining_grammar_hits;
                            remainingautoeval_hits = response.remaining_autoeval_hits;
                            
                            if(teacherPermission == 1 && isAdmin == 0) {
                                $("#apimessage").html("No. of automated evaluations available: " + remainingautoeval_hits);
                            }
                        }
                    });
                
                    $("#loader").hide();
                    return request;
                };
                  
                // Automated evaluation
                $("#id_automatedevaluation").click(() => {
                    let checkAvailableAutoevalAPI = remainingautoeval_hits;
                    if(dataToCheck == null) {
                        $('.validation').html("");
                        $(".validation").show(); $(".success").hide();
                        $(".validation").show(); $(".success").hide();
                        $('.validation').append("<h5 style='color:#ff0000'>Automated evaluation feedback </h5><img src='pix/suggestion.jpg' alt='suggestion' width='30'> This student have not submitted his assignment yet.</h5><br/><br/>");
                        
                    } else {
                    
                        if(checkAvailableAutoevalAPI || isAdmin == 1) {
                            $(".validation").hide();
                            $(".success").hide();
                            $(".validation").html("");
                            automateEval(dataToCheck, testlink).then(response =>{
								
                                let status = response.status;
                                if(status){
                                    let result = response.result;
                                    let bandScore = response.score;
                                    $("#id_grade").val(bandScore);
                                    //$(".validation").show(); 
                                    $(".success").hide();
                                    // template-append
                                    $('#id_fastassignfeedbackcomments_editoreditable, #id_fastassignfeedbackcomments_editor').html(result);
                                    //$('#id_fastassignfeedbackcomments_editoreditable, #id_fastassignfeedbackcomments_editor, .validation').html(result);
                                    $(".language-accuracy-lists").css({
                                        "list-style-type": "none"
                                    });
                                }
                                // when returns error from API
                                else{
									
                                    let error = response.error;
                                    $(".validation").show(); $(".success").hide();
                                    $('.validation').append(`
                                        <h5 style='color:#ff0000'>Automated Evaluation: <img src='pix/suggestion.jpg' alt='suggestion' width='30'></h5>
                                        <p><b>Error from API: </b> <span style='color: #ff0000'>${error}</span></p>
                                    `);
                                    
                                    /*$('#id_fastassignfeedbackcomments_editoreditable, #id_fastassignfeedbackcomments_editor').append(`
                                        <h5 style='color:#ff0000'>Automated Evaluation: <img src='pix/suggestion.jpg' alt='suggestion' width='30'></h5>
                                        <p><b>Error from API: </b> <span style='color: #ff0000'>${error}</span></p>
                                    `);*/
                                }
                            });
                        } else {
                            $('.validation').html("");
                            $(".validation").show(); $(".success").hide();
                            $(".validation").show(); $(".success").hide();
                            $('.validation').append("<h5 style='color:#ff0000'>Automated evaluation feedback </h5><img src='pix/suggestion.jpg' alt='suggestion' width='30'> Your maximum no. of checks for automated evaluation API is finished.</h5><br/><br/>");
                        }
                    }
                });
            }
        });
    }
});
