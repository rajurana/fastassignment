$(document).ready(function () {
    let ajaxUrrl = "ajax_handler.php";
    if(window.location.href.indexOf("course/modedit.php")>0){
      ajaxUrrl = "../mod/fastassignment/"+ajaxUrrl;
    }

    $.ajax({
        url: ajaxUrrl,
        data: {
            settingsApi: 1
        },
        type: "GET",
        success: function(response) {
            response = JSON.parse(response, true);
            const NODE_URL = "https://console.virtualwritingtutor.com/console";
            const API_KEY = response.main_api;
            $("input[name*='api_key']").val(API_KEY);
            $("input[name*='api_key_feedback']").val(API_KEY);
            // Get category data from Node API
            let essayTasks = (essayTasksNew = essayTaskNew = "");
            const getCategories = async () => {
                let myHeaders = new Headers();
                myHeaders.append("vwtapikey", API_KEY);
                let requestOptions = {
                  method: "GET",
                  headers: myHeaders,
                  redirect: "follow",
                };
                const request = await (
                  await fetch(`${NODE_URL}/essay/essay-tasks`, requestOptions)
                ).json();
                return request;
            };
            
            //////////////////////
            // CATEGORY TESTS
            //////////////////////
            const getCategoryTest = async (endpoint) => {
              let myHeaders = new Headers();
              myHeaders.append("vwtapikey", API_KEY);
              let requestOptions = {
                method: "GET",
                headers: myHeaders,
                redirect: "follow",
              };
              const request = await (
                await fetch(`${NODE_URL}/essay/test-list/${endpoint}`, requestOptions)
              ).json();
              return request;
            };
            
            const getTestNfo = async (info_endpoint) => {
              let myHeaders = new Headers();
              myHeaders.append("vwtapikey", API_KEY);
              let requestOptions = {
                method: "GET",
                headers: myHeaders,
                redirect: "follow",
              };
              const request = await (
                await fetch(
                  `${NODE_URL}/essay/test-info/${info_endpoint}`,
                  requestOptions
                )
              ).json();
              return request;
            };
        
            const checkAPILimits = async (apiKeyy) => {
              let myHeaders = new Headers();
              myHeaders.append("vwtapikey", apiKeyy);
              let requestOptions = {
                method: "GET",
                headers: myHeaders,
                redirect: "follow",
              };
              const request = await (
                await fetch(`${NODE_URL}/auth/info`, requestOptions)
              ).json();
              return request;
            };
            
          // ASYNC CODES
          essayTaskNew = getCategories().then((essayTaskNew) => {
            try {
              // Prepare options of select category
              let select = document.getElementById("id_category");
              essayTaskNew.result.unshift({
                task_name: "Select Category",
                taskt_link: "#",
              });
              essayTaskNew.result.map((item, i) => {
                let opt = document.createElement("option");
                opt.value = i;
                opt.innerHTML = item.task_name;
                select.appendChild(opt);
              });
      
              // Set initial option value for tests
              let loading = document.getElementById("id_test");
              let opt = document.createElement("option");
              opt.value = "";
              opt.innerHTML = "Select Test";
              loading.appendChild(opt);
      
              // Prepare options of Tests
              $("#id_category").change(function () {
                let selectedCategory = $(this).children("option:selected").html();
                // Create endpoint for tests
                let endpointTest = selectedCategory.toLowerCase();
                endpointTest = endpointTest.replace(/ /g, "-");
                /* console.log("endpoint", endpointTest); */
                let data = getCategoryTest(endpointTest).then((data) => {
                  // reset prev options before on change
                  $("#id_test").find("option").remove().end();
                  // Prepare options of select category
                  let select = document.getElementById("id_test");
                  data.result.unshift({ test_name: "Select Tests", test_link: "#" });
                  data.result.map((item, i) => {
                    let opt = document.createElement("option");
                    opt.value = item.test_link;
                    opt.innerHTML = item.test_name;
                    select.appendChild(opt);
                  });
                });
              });
            } catch(e) {
              $("#id_validate_api_limits")
              .html("Invalid API key! Please go to pluign settings to update valid API key or buy essay hits from <a href='https://virtualwritingtutor.com/get-an-api-key/' target='_blank'>virtualwritingtutor.com/get-an-api-key</a>")
              .addClass("btn btn-danger");
            }
          });
          
          ///////////////////////////////////////////
          // APPENDING DATA IN EDITOR ON ADD CLICK
          //////////////////////////////////////////
          $("#id_add_to_editor").click(() => {
            let info_endpoint = $("#id_test").val();
            let request = getTestNfo(info_endpoint).then((data) => {
              let dataToSubmit = data.result.test_question;
              let test_name = data.result.test_name;
              let test_image = data.result.test_image;
              if(!!test_image) {
                let ieltsChart = `<img src="${test_image}" alt='${test_name}' width='400'/>`;
                dataToSubmit = dataToSubmit + "<p>"+ ieltsChart + "</p>";
              }
              
              $("#id_introeditoreditable").val();
              $("#id_introeditoreditable").append(dataToSubmit);
            });
          });
                
          $("#id_add_to_editor")
          .prop("disabled", true)
          .prop("title", "you must select categories and tests first.")
          .css("cursor", "not-allowed");
                
                  let selected_test_val = "";
                  let selected_category_val = "";
                  $("select#id_test").change(function () {
                    $("#id_add_to_editor").prop("disabled", false).css("cursor", "pointer");
                    let selected_test = $(this).val();
                    let selected_testname = $("#id_test option:selected").text();
                    let selected_catname = $("#id_category option:selected").text();
                
                    $("input[name='testlinks_hidden']").val(selected_test);
                    $("input[name='test_name']").val(selected_testname);
                    $("input[name='category_name']").val(selected_catname);
                  });
                  $("select#id_category").change(function () {
                    $("#id_add_to_editor").prop("disabled", false).css("cursor", "pointer");
                    let selected_category = $(this).val();
                  });
                  // Validate API limit checks
                  $("#id_validate_api_limits").click(() => {
                    if (!!API_KEY) {
                      checkAPILimits(API_KEY).then((data) => {
                        const clientName = data.result.client_name;
                        const remainingGrammarHits = data.result.remaining_grammar_hits;
                        const remainingEssayHits = data.result.remaining_essay_hits;
                
                        const usedGrammarHits = data.result.used_grammar_hits;
                        const usedEssayHits = data.result.used_essay_hits;
                
                        const totalGrammarHits = data.result.total_grammar_hits;
                        const totalEssayHits = data.result.total_essay_hits;
                
                        const scoreGrammar = usedGrammarHits + "/" + totalGrammarHits;
                        const scoreEssay = usedEssayHits + "/" + totalEssayHits;
                
                        $("#id_validate_api_limits")
                          .html(
                            `Validated<br/> Client Name: ${clientName} <br> 
                              Remaining Grammar hits:  ${remainingGrammarHits} hits remaining (${scoreGrammar}  used) <br/>
                              Remaining Essay hits:  ${remainingEssayHits} hits remaining (${scoreEssay} used)`
                          )
                          .removeClass("btn btn-danger")
                          .addClass("btn btn-success");
                      }).catch(function (e) {
                        $("#id_validate_api_limits")
                        .html("Invalid API key! Please go to pluign settings to update valid API key or buy essay hits from <a href='https://virtualwritingtutor.com/get-an-api-key/' target='_blank'>virtualwritingtutor.com/get-an-api-key</a>")
                        .addClass("btn btn-danger");
                      });
                    } else {
                      $("#id_validate_api_limits")
                        .html("API key missing!")
                        .addClass("btn btn-danger");
                    }
                  });
        }
    });
});
