var ae_products_cache = new Map();
var ae_products_list = [];
var ae_products_status = ['onSelling','offline'];
var ae_current_status = '';
var ae_products_error = '';
const ae_page_size = 10;
jQuery(document).ready(function($) {

    aewpreloader = {
        show: function(msg){
            if (msg && msg != '') { mensaje = msg; }else{ mensaje = ALIWOOLang.loading; }
            if($(".darkAlert").length == 0) {
                $("body").append('<div class="darkAlert"><div class="awePreloader"><img src="'+AEW_Data.assetsPath+'/loading.gif" /><div class="text">'+mensaje+'</text></div></div>');
            }else{
                $("body .awePreloader").replaceWith('<div class="awePreloader"><img src="'+AEW_Data.assetsPath+'/loading.gif" /><div class="text">'+mensaje+'</text></div>')
            }
        },
        hide: function(){
            $(".darkAlert").remove();
        }
    }
    instanceJSTREE = null;
    categoriaSeleccionada = null;

    $(document).on("click", "#bt_ae_get_catalog", function(){
        var dest = $("#ae_catalog_container");
        $( this ).hide();
        $("#aew_products_table").find("tr:gt(0)").remove();
        ae_products_cache = new Map();
        if(ae_products_status.length==0){
            ae_products_status = ['onSelling','offline'];
        }
        ae_current_status = ae_products_status.shift();
        dest.html("Loading products (" + ae_current_status + ")...");
        $('#wpfooter').hide();
        get_ae_products_page(1);
    });

    $(document).on("change", '#ae_pg_select', function() {
        ae_load_page();
    });

    $(document).on("input", '#ae_filter_box', function() {
        end_ae_products();
    });

    $(document).on("click", "#ae_pg_prev", function(){
        var pg = parseInt($('#ae_pg_select').val());
        if(pg>1){
            pg --;
            $('#ae_pg_select').val(pg);
            ae_load_page();
        }
    });
    $(document).on("click", "#ae_pg_next", function(){
        var pg = parseInt($('#ae_pg_select').val());
        const max = parseInt($('#ae_pg_select option:last').val());
        if(pg<max){
            pg ++;
            $('#ae_pg_select').val(pg);
            ae_load_page();
        }
    });
    function ae_load_page(){
        const first = (parseInt($('#ae_pg_select').val()) - 1) * ae_page_size ;
        show_ae_products_list(ae_products_list.slice(first, first + ae_page_size));
    }

    function progress_ae_products(current,total){
        var dest = $("#ae_catalog_container");
        dest.html("Loading page " + current + " of " + total + " (" + ae_current_status + ")");
    }

    function end_ae_products(){
        if(ae_products_status.length>0){
            ae_current_status = ae_products_status.shift();
            var dest = $("#ae_catalog_container");
            dest.html("Loading products (" + ae_current_status + ")...");
            get_ae_products_page(1);
            return;
        }
        ae_current_status = '';
        var dest = $("#ae_catalog_container");
        dest.html(ae_products_cache.size + " Products");
        $("#bt_ae_get_catalog").show();
        var ae_ss = $("#ae_filter_box").val();
        if(ae_ss!=''){
            ae_products_list = filter_ae_products(ae_ss);
        }else{
            ae_products_list = Array.from(ae_products_cache.keys());
        }
        show_ae_pagination(Math.ceil(ae_products_list.length/ae_page_size));
        show_ae_products_list(ae_products_list.slice(0,ae_page_size));
    }

    function show_ae_pagination(npages){
        console.log('paginando en ' + npages);
        var options = '<option value="1">1</option>';
        for (var i = 2; i <= npages; i++) {
            options += '<option value="'+i+'">'+i+'</option>';
        };
        $('#ae_pg_select')
            .find('option')
            .remove()
            .end()
            .append(options)
            .val('1')
        ;
    }

    function show_ae_products_list(list){
        $("#aew_products_table").find("tr:gt(0)").remove();
        list.forEach(function(key, index){
            value=ae_products_cache.get(key);
            var status = 'unmapped';
            if(ae_local_products.hasOwnProperty(key)){
                status = 'mapped';
            }
            var online = '<input type="checkbox" checked="checked" disabled="disabled">';
            if(value.status!='onSelling'){
                online = '<input type="checkbox" disabled="disabled">';
            }
            var row = '<tr><td><img width="50" src="' + value.image_u_r_ls + '" />';
            row += '</td><td><a target="_blank" href="https://www.aliexpress.com/item/' + key + '.html">'+ key + '</a>';
            row += '</td><td>' + online;
            row += '</td><td>' + value.subject;
            if(status=='mapped'){
                row += '</td><td><a target="_blank" href="post.php?post=' + ae_local_products[key].id + '&action=edit" title="';
                row += ae_local_products[key].name + '">' + status + '</a>';
            }else{
                row += '</td><td>' + status;
            }

            row += '</td><td>';
            // actions
            if(status=='unmapped'){
                row += '<a href="javascript:void(0);" class="button createProductFromAE" data-id="' + key + '"> Create in WooCommerce </a>';
            }
            if(status=='mapped'){
                row += '<a href="javascript:void(0);" class="button editAEId" data-id="' + key + '"> Edit AliExpress Id </a>';
            }
            row += '</td></tr>';
            $("#aew_products_table tr:last").after(row);
        });
    }

    function get_ae_products_page(npage){
        $.ajax({
            url: AEW_Data.adminAjaxURL+'?action=aew_get_catalog_ae',
            method: 'POST',
            dataType: 'JSON',
            data: {
                page: npage,
                status: ae_current_status
            },
            success: function(res) {
                var r;
                //console.log(res);
                try {
                    r = JSON.parse(res);
                } catch (e) {
                    ae_products_error = "Response error";
                    return;
                }

                if(typeof r.error != 'undefined') {
                    ae_products_error = r.error;
                    return;
                }
                if(typeof r.result != 'undefined') {
                    if(r.result.product_count==0){
                        ae_products_error= "0 products";
                        end_ae_products();
                        return;
                    }
                    if(r.result.aeop_a_e_product_display_d_t_o_list.item_display_dto){
                        var items = r.result.aeop_a_e_product_display_d_t_o_list.item_display_dto;
                        if(typeof items.product_id != 'undefined'){
                            items = [items];
                        }
                        items.forEach(function (e,index){
                            //console.log(e);
                            e.status = ae_current_status;
                            ae_products_cache.set(e.product_id, e);
                        });
                    }
                    if(r.result.total_page>npage){
                        progress_ae_products(npage, r.result.total_page);
                        get_ae_products_page(npage+1);
                        return
                    }else{
                        // hemos terminado
                        end_ae_products();
                    }
                }
            }, error: function(err) {
                console.log(err);
                ae_products_error = res.error;
            }
        });
    }

    $(document).on("click", ".createProductFromAE", function(){
        var id = $(this).attr("data-id");

        if(id == '') {
            return;
        }
        console.log("CREATING");
        // $(this).prop("disabled", true);
        $(this).html(ALIWOOLang.CreatingWooCommerceProduct);
        var elem = $(this);
        $.ajax({
            url: AEW_Data.adminAjaxURL+'?action=aew_get_product_by_idae',
            method: 'POST',
            dataType: 'JSON',
            data: {
                idpro: id
            },
            success: function(res) {
                console.log(res);
                if(typeof res.product != 'undefined') {
                    elem.prop("disabled", false);
                    elem.html(ALIWOOLang.viewProduct).prop('disabled', false).attr("target", "_blank").attr('href', '/wp-admin/post.php?post='+res.product+'&action=edit').css({'background-color': 'green', 'color': '#FFF'});
                    if(typeof res.exist != 'undefined') {
                        elem.closest("td").append('<br>'+res.exist);
                    }
                }

                if(typeof res.error != 'undefined') {
                    elem.replaceWith('<p style="color:red">'+res.error+'</p>');
                }
            }, error: function(err) {
                console.log(err);
            }
        });
    });
    // Mostrar el boton de relacionar con categoria de AliExpress en Configuración
    $(document).on("click", ".line_category input[type='checkbox']", function(){
        if($(this).prop("checked")) {
            $(".button_category_aliexpress[data-id='" + $(this).val() + "']").show();
        }else{
            $(".button_category_aliexpress[data-id='" + $(this).val() + "']").hide();
        }
    });

    // Mostrar el boton de relacionar con atributo de AliExpress en Configuración
    $(document).on("click", ".line_attribute input[type='checkbox']", function(){
        if($(this).prop("checked")) {
            $(".button_attribute_aliexpress[data-id='" + $(this).val() + "']").show();
        }else{
            $(".button_attribute_aliexpress[data-id='" + $(this).val() + "']").hide();
        }
    });


    $(document).on("change", ".featureDefaultSelect", function(){
        var feature = $(this).attr("data-feature");
        if($(this).val() == '4') {

            if($(".params_required .feature[data-feature='"+feature+"'] input").length == 0) {
                $(".params_required .feature[data-feature='"+feature+"']").append('<input type="text" name="defaultInputFeaturesCustom['+feature+']" placeholder="'+ALIWOOLang.CustomValue+'" />');
            }
        }else{
            //console.log("Borrando", $(".params_required  input[name='defaultInputFeatures["+feature+"]']").length);
            $(".params_required .feature[data-feature='"+feature+"']").find("input").eq(0).remove();
        }
    });

    $(document).on("click", "#importCategoryAew", function(){
        aewpreloader.show();
        var id = $(this).attr("data-id");
        $.ajax({
            url: AEW_Data.adminAjaxURL+'?action=aew_upload_category_job',
            method: 'POST',
            dataType: 'JSON',
            data: {
                category: id
            },
            success: function(res) {
                aewpreloader.hide();
                if(!res) {
                    Swal.fire({
                        title: 'Error',
                        text: ALIWOOLang.seeLogError,
                        type: 'error',
                    });
                    return;
                }
                Swal.fire({
                    title: ALIWOOLang.correct,
                    text: res.length + " " + ALIWOOLang.productsToAE,
                    type: 'success',
                });
            }, error: function(err) {
                aewpreloader.hide();
                console.log(err);
            }
        });
    });

    function AEWsplitArrayIntoChunksOfLen(arr, len) {
        var chunks = [], i = 0, n = arr.length;
        while (i < n) {
            chunks.push(arr.slice(i, i += len));
        }
        return chunks;
    }


    //Delete products
    $(document).on("click", "#deleteAEProducts", function(){
        if(confirm(ALIWOOLang.sureDeleteProducts)) {
            aewpreloader.show(ALIWOOLang.calculateProducts);
            var id = $(this).attr("data-id");
            $.ajax({
                url: AEW_Data.adminAjaxURL+'?action=aew_get_ids_category',
                method: 'POST',
                dataType: 'JSON',
                data: {
                    category: id
                },
                success: function(res) {
                    aewpreloader.hide();
                    if(!res) {
                        Swal.fire({
                            title: 'Error',
                            text: ALIWOOLang.seeLogError,
                            type: 'error',
                        });
                        return;
                    }
                    var nActualDelete = 0;
                    aewpreloader.show(nActualDelete + ' / ' + res.length);

                    var allProducts = AEWsplitArrayIntoChunksOfLen(res, 50);

                    //Enviar Eliminación
                    for(p in allProducts) {
                        var deletedConfirm = aew_delete_products(allProducts[p]);
                        nActualDelete = nActualDelete + p.length;
                        aewpreloader.show(nActualDelete + ' / ' + res.length);
                    }

                    setTimeout(function(){
                        aewpreloader.hide();
                        Swal.fire({
                            title: 'Success',
                            text: 'Completed',
                            type: 'success',
                        });
                    });


                }, error: function(err) {
                    aewpreloader.hide();
                    console.log(err);
                    alert("Error to get products delete.")
                }
            });
        }

    });


    //Disable products
    $(document).on("click", "#disableAEProducts", function(){
        if(confirm(ALIWOOLang.sureDisableProducts)) {
            aewpreloader.show();
            var id = $(this).attr("data-id");
            $.ajax({
                url: AEW_Data.adminAjaxURL+'?action=aew_get_ids_category',
                method: 'POST',
                dataType: 'JSON',
                data: {
                    category: id
                },
                success: function(res) {
                    aewpreloader.hide();
                    if(!res) {
                        Swal.fire({
                            title: 'Error',
                            text: ALIWOOLang.seeLogError,
                            type: 'error',
                        });
                        return;
                    }

                    var allProducts = AEWsplitArrayIntoChunksOfLen(res, 50);

                    //Enviar Eliminación
                    for(p in allProducts) {
                        var deletedConfirm = aew_disable_products(allProducts[p]);
                        nActualDelete = nActualDelete + p.length;
                        aewpreloader.show(nActualDelete + ' / ' + res.length);
                    }

                    setTimeout(function(){
                        aewpreloader.hide();
                        Swal.fire({
                            title: 'Success',
                            text: 'Completed',
                            type: 'success',
                        });
                    });


                }, error: function(err) {
                    aewpreloader.hide();
                    console.log(err);
                    alert("Error to get products disable.")
                }
            });
        }

    });

    //Active products
    $(document).on("click", "#activeAEProducts", function(){
        if(confirm(ALIWOOLang.sureActiveProducts)) {
            aewpreloader.show();
            var id = $(this).attr("data-id");
            $.ajax({
                url: AEW_Data.adminAjaxURL+'?action=aew_get_ids_category',
                method: 'POST',
                dataType: 'JSON',
                data: {
                    category: id
                },
                success: function(res) {
                    aewpreloader.hide();
                    if(!res) {
                        Swal.fire({
                            title: 'Error',
                            text: ALIWOOLang.seeLogError,
                            type: 'error',
                        });
                        return;
                    }

                    var allProducts = AEWsplitArrayIntoChunksOfLen(res, 50);

                    //Enviar Eliminación
                    for(p in allProducts) {
                        var deletedConfirm = aew_active_products(allProducts[p]);
                        nActualDelete = nActualDelete + p.length;
                        aewpreloader.show(nActualDelete + ' / ' + res.length);
                    }

                    setTimeout(function(){
                        aewpreloader.hide();
                        Swal.fire({
                            title: 'Success',
                            text: 'Completed',
                            type: 'success',
                        });
                    });


                }, error: function(err) {
                    aewpreloader.hide();
                    console.log(err);
                    alert("Error to get products active.")
                }
            });
        }

    });



    $(document).on("click", ".regen_token_cron", function(){
        aewpreloader.show();
        $.ajax({
            url: AEW_Data.adminAjaxURL+'?action=aew_regenerate_token_cron',
            method: 'POST',
            dataType: 'JSON',
            success: function(res) {
                aewpreloader.hide();
                if(res['success']) {
                    window.location.reload();
                }
            }, error: function(err) {
                aewpreloader.hide();
                console.log(err);
            }
        });
    });
    //Subir a AliExpress
    $(document).on("click", "button.aew_publish", function(){
        aewpreloader.show();
        var self = $(this);
        $(this).text(ALIWOOLang.Wait).prop("disabled",true);
        $.ajax({
            method : "post",
            url : AEW_Data.adminAjaxURL+'?action=aew_upload_product',
            data : {
                id_p: $(this).attr("data-id")
            },
            dataType: 'JSON',
            error: function(response){
                aewpreloader.hide();
                Swal.fire({
                    title: 'Error',
                    html: ALIWOOLang.seeLogError + ', <a href="/wp-admin/admin.php?page=aliexpress-general-options&tab=error" target="_blank">' + ALIWOOLang.clickHere + '</a>',
                    type: 'error',
                });
                console.log(response);
                self.text(ALIWOOLang.Upload).prop("disabled",false);
                console.log(response);

            },
            success: function(response) {
                aewpreloader.hide();
                if(!response) {
                    Swal.fire({
                        title: 'Error',
                        html: ALIWOOLang.seeLogError + ', <a href="/wp-admin/admin.php?page=aliexpress-general-options&tab=error" target="_blank">' + ALIWOOLang.clickHere + '</a>',
                        type: 'error',
                    });
                    self.text(ALIWOOLang.Upload).prop("disabled",false);
                    return;
                }
                self.parent('td').html(ALIWOOLang.Uploading);
            }
        })
    });

    /**
     * Enable Tooltip
     */
    $('.tooltip_ae').tooltipster({
        theme: 'tooltipster-borderless'
    });

    $(document).on("click", "button.aew_publish_update", function(){
        aewpreloader.show();
        console.log("button update");
        var self = $(this);
        self.text(ALIWOOLang.Updating).prop("disabled",true);
        $.ajax({
            method : "post",
            url : AEW_Data.adminAjaxURL+'?action=aew_update_product',
            data : {
                id_p: self.attr("data-id")
            },
            dataType: 'JSON',
            error: function(response){
                aewpreloader.hide();
                Swal.fire({
                    title: 'Error',
                    html: ALIWOOLang.seeLogError + ', <a href="/wp-admin/admin.php?page=aliexpress-general-options&tab=error" target="_blank">' + ALIWOOLang.clickHere + '</a>',
                    type: 'error',
                });
                console.log(response);
                self.text(ALIWOOLang.Update).prop("disabled",false);
                console.log(response);

            },
            success: function(response) {
                aewpreloader.hide();
                if(!response) {
                    self.text(ALIWOOLang.Update).prop("disabled",false);
                    Swal.fire({
                        title: 'Error',
                        text: ALIWOOLang.seeLogError,
                        type: 'error',
                    });
                    return;
                }
                console.log(response);
                self.text(ALIWOOLang.Update).prop("disabled",false);
                return;
                console.log(response);
                //   self.text("Success").removeClass("aew_publish_").addClass("aew_view").attr("data-product-id", response.id_p.result.product_id).prop("disabled",false);
                //   setTimeout(function(){
                //     self.delay(1000).text("View");
                //   },2000);
            }
        })
    });

    $(document).on("click", "button.aew_view", function(){
        var id = $(this).attr("data-product-id");
        window.open('https://www.aliexpress.com/item/'+id+'.html', '_blank');
    });


    $(document).on("submit", "form#formLoginAew", function(e){
        if($(this).attr("action") != '') {
            console.log("URL", $(this).attr("action"));
            return true;
        }
        $(".authorize", this).prop("disabled", true);
        e.preventDefault();
        e.stopPropagation();
        if($("input[name='email_aliexpress']").val() == "" || $("input[name='url_callback_aliexpress']").val() == "") {
            Swal.fire({
                title: 'Error',
                html: ALIWOOLang.emailorCallbackNoAvailable,
                type: 'error',
            });
            $(".authorize", this).prop("disabled", false);
            return false;
        }
        var urlLogin = 'https://aliexpress.wecomm.es/services/gettoken/' + btoareplace($("input[name='email_aliexpress']").val()) + '/' + btoareplace($("input[name='url_callback_aliexpress']").val()) + '/' + $("input[name='aew_lang_wp']").val() + '/';
        // console.log(urlLogin);
        $(this).attr("action", urlLogin).submit();

    });

    function btoareplace(string) {
        var string = btoa(string);
        var string = string.replace(/\+/g,'-');
        var string = string.replace(/\//g,'_');
        var string = string.replace(/\=/g,'');
        return string;
    }

    $(document).on("change", "select[name='category_suggest']", function(){
        categoriaSeleccionada = {
            id: $("option:selected", $(this)).val(),
            text: $("option:selected", $(this)).attr("data-name"),
        }
    });

    $(document).on("click", "a.aew_get_category", function(){
        categoriaSeleccionada = null;
        var id = $(this).attr("data-id");
        var elemBoton = $(this);
        aewpreloader.show();
        $.ajax({
            method : "post",
            url : AEW_Data.adminAjaxURL+'?action=aew_get_category_suggest',
            data : {
                category: id,
            },
            dataType: 'JSON',
            success: function(response) {
                aewpreloader.hide();
                if(response['error']) {
                    alert("AliExpress error, please contact with support");
                    return;
                }
                var sugeridas = '';

                for(c in response['result']['suggest_category_list']['suggest_category']) {

                    var stringSugeridas = response['result']['suggest_category_list']['suggest_category'][c]['name_path']['string'].join(' / ');
                    sugeridas += '<option data-name="'+response['result']['suggest_category_list']['suggest_category'][c]['name']+'" value="'+response['result']['suggest_category_list']['suggest_category'][c]['id']+'">'+stringSugeridas+'</option>';
                }

                Swal.fire({
                    title: ALIWOOLang.SelectCategory,
                    html: '<div><select name="category_suggest"><option value="0">'+ALIWOOLang.selectOne+'</option>'+sugeridas+'</select></div>',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                }).then((result) => {
                    if (result.value) {
                        aewpreloader.show();

                        $.ajax({
                            method : "post",
                            url : AEW_Data.adminAjaxURL+'?action=aew_set_category_aliexpress',
                            data : {
                                c_local: id,
                                c_remote: categoriaSeleccionada.id,
                                category_name: categoriaSeleccionada.text
                            },
                            dataType: 'JSON',
                            success: function(response) {
                                aewpreloader.hide();
                                elemBoton.closest('td').html(response.name);
                                categoriaSeleccionada = null; //Reset Category
                            },
                            error: function(response){
                                aewpreloader.hide();
                                Swal.fire({
                                    title: 'Error',
                                    text: ALIWOOLang.errorCategorySet,
                                    type: 'error',
                                });
                                console.log(response);

                            }
                        })
                        categoriaSeleccionada = null; //Reset Category
                    }
                })
            },
            error: function(response){
                aewpreloader.hide();
                Swal.fire({
                    title: 'Error',
                    text: ALIWOOLang.errorCategorySet,
                    type: 'error',
                });
                console.log(response);

            }
        })
    });



    /***
     * AliExpress Id Editor
     */

    $(document).on("click", ".editAEId", function(){
        var id = $(this).attr("data-id");

        if(id == '') {
            return;
        }

        Swal.fire({
            title: ALIWOOLang.editAEIdTitle,
            html: '<input type="text" id="new_idae" value="'+id+'">',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: ALIWOOLang.btEdit,
            cancelButtonText: ALIWOOLang.cancel,
            showCancelButton: true,
        }).then((result) => {
            if (result.value) {
                var newid = $("#new_idae").val();
                if(!ae_products_cache.has(newid)){
                    Swal.fire({
                        title: 'Error',
                        text: ALIWOOLang.editAEIdNotFound,
                    });
                    return;
                }
                if(ae_local_products.hasOwnProperty(newid)){
                    Swal.fire({
                        title: 'Error',
                        text: ALIWOOLang.editAEIdAlreadyMapped,
                    });
                    return;
                }
                if(id==newid){
                    return;
                }
                aewpreloader.show();
                $.ajax({
                    method : "post",
                    url : AEW_Data.adminAjaxURL+'?action=aew_set_new_id_aliexpress',
                    data : {
                        id_old: id,
                        id_new: newid,
                        wc_id: ae_local_products[id].id
                    },
                    dataType: 'JSON',
                    success: function(response) {
                        if(response.success){
                            ae_local_products[response.newid] = ae_local_products[response.oldid];
                            delete ae_local_products[response.oldid];
                            ae_load_page();
                            aewpreloader.hide();
                            Swal.fire({
                                title: ALIWOOLang.correct,
                                text: ALIWOOLang.jobFinished,
                            });
                        }
                    },
                    error: function(response){
                        aewpreloader.hide();
                        Swal.fire({
                            title: 'Error',
                            text: '',
                            type: 'error',
                        });

                    }
                });
            }
        });

    });


    /***
     * Categories
     */

    $(document).on("click", "button.button_category_aliexpress", function(){
        categoriaSeleccionada = null;
        var id = $(this).attr("data-id");
        Swal.fire({
            //type: 'error',
            title: ALIWOOLang.SelectCategory,
            html: '<div id="tree'+id+'"></div>',
            footer: ALIWOOLang.AECategories,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: ALIWOOLang.SelectThisCategory + '<div class="seleccionada"></div>',
            customClass: {
                popup: 'seleccionadorDeCategorias'
            },
            onOpen: function (){
                Swal.disableConfirmButton();
            }
        }).then((result) => {
            if (result.value) {
                aewpreloader.show();
                $.ajax({
                    method : "post",
                    url : AEW_Data.adminAjaxURL+'?action=aew_set_category_aliexpress',
                    data : {
                        c_local: id,
                        c_remote: categoriaSeleccionada.id,
                        category_name: categoriaSeleccionada.text
                    },
                    dataType: 'JSON',
                    success: function(response) {
                        aewpreloader.hide();
                        if(response.success) {
                            console.log("Categoria conectada");
                            $(".line_category[data-cat='"+id+"'] td.aliexpress-name").html(categoriaSeleccionada.text);
                        }
                        categoriaSeleccionada = null; //Reset Category
                    },
                    error: function(response){
                        aewpreloader.hide();
                        Swal.fire({
                            title: 'Error',
                            text: ALIWOOLang.errorCategorySet,
                            type: 'error',
                        });
                        console.log(response);

                    }
                })
                console.log("Estableciendo categoria como " + categoriaSeleccionada.text + " con id " + categoriaSeleccionada.id + " categoria local " + id);
            }
        });
        $('#tree'+id).on('changed.jstree', function (e, data) {
            instanceJSTREE = $("#tree"+id);
            var i, j, r = [];
            for(i = 0, j = data.selected.length; i < j; i++) {
                r.push(data.instance.get_node(data.selected[i]).text);
            }
            console.log("Categoria Seleccionada ");
            Swal.enableConfirmButton();
            $(".seleccionadorDeCategorias button.swal2-confirm .seleccionada").html('').append(data.node.text);
            categoriaSeleccionada = data.node;
            console.log(data);
        }).jstree({
            "check_callback" : true,
            "themes" : { "stripes" : true },
            "core" : {
                'strings' : {
                    'Loading ...' : ALIWOOLang.CargandoCategories
                },
                'data' : {
                    'url' : AEW_Data.adminAjaxURL+'?action=aew_get_categories',
                    "dataType" : "json",
                    "data" : function (node) {
                        if(node.id === '#'){ return; }
                        return { 'id': node.id };
                    }
                }
            },
            "plugins" : [
                "wholerow",
                "search"
                // "checkbox", 
                //"state", 
                //"massload"
            ]
        });
    });


    $(document).on("click", "button.addCategoryToMapping", function(){
        var idCat = $("select[name='cat']").val();
        console.log(idCat);
        if(idCat == null || idCat == '' || idCat == '0' || $(".categories_mapping_table .line_category[data-cat='"+idCat+"']").length > 0) {

            return;
        }
        var catName = $("select[name='cat'] option:selected").text();
        $(".categories_mapping_table").append('<tr class="line_category " data-cat="'+idCat+'">'+
            '<td><strong>'+catName+'</strong></td>'+
            '<td class="aliexpress-name"><a href="javascript:void(0)" data-id="'+idCat+'" class="aew_get_category">Suggest Category</a></td>'+
            '<td class="category_fee"><input type="text" size="5" name="feeCategory['+idCat+']" value=""></td><td class="fixed_amount"><input type="text" size="5" name="fixedAmount['+idCat+']" value=""></td><td class="medidas"><input type="text" size="3" name="medidas['+idCat+'][width]" value="1" placeholder="Ancho"><input type="text" size="3" name="medidas['+idCat+'][length]" value="1" placeholder="Largo"><input type="text" size="3" name="medidas['+idCat+'][height]" value="1" placeholder="Alto"><input type="text" size="3" name="medidas['+idCat+'][weight]" value="1" placeholder="Peso"></td><td><input type="text" name="preparation['+idCat+']" value="3" size="3"></td><td></td><td></td>'+
            '<td><input type="text" name="aliexpress_tags['+idCat+']" value=""> </td><td><input type="checkbox" name="aliexpress_add_brand['+idCat+']" value="1"></td>'+

            '<td class="aliexpress_category">'+
            '<input type="hidden" name="aliexpress-category'+idCat+'" value="">'+
            '<button type="button" class="ae_button button_category_aliexpress" data-id="'+idCat+'">Selecciona</button>'+
            '</td><td class="unlink-button"></td></tr>');

        //Delete selected category
        $("select[name='cat'] option:selected").remove();

        if($("select[name='cat'] option").length == 0) {
            $("div.block_mapping_category").remove();
        }
    });



    $('#awe_search_category').keyup(function () {
        if(!instanceJSTREE) { console.log("no instance"); return;}
        if(to) { clearTimeout(to); }
        to = setTimeout(function () {
            var v = $('#awe_search_category').val();
            instanceJSTREE.jstree(true).search(v);
        }, 250);
    });

    $(document).on("click", ".removeCategoryAliExpress", function(){
        var id = $(this).attr("data-id");
        Swal.fire({
            title: ALIWOOLang.AreYouSure,
            text: ALIWOOLang.norevert,
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: ALIWOOLang.yesDelete
        }).then((result) => {
            if (result.value) {
                aewpreloader.show();
                $.ajax({
                    method : "post",
                    url : AEW_Data.adminAjaxURL+'?action=aew_remove_category_aliexpress',
                    data : {
                        c_local: id,
                    },
                    dataType: 'JSON',
                    success: function(response) {
                        aewpreloader.hide();
                        $(".line_category[data-cat='"+id+"'] td.aliexpress-name").html('');
                        $(".line_category[data-cat='"+id+"'] td.unlink-button").html('');
                        $(".line_category[data-cat='"+id+"'] td.category_fee").html('');
                    },
                    error: function(response){
                        aewpreloader.hide();
                        Swal.fire({
                            title: ALIWOOLang.error,
                            text: ALIWOOLang.errorRemoveCategoryAE,
                            type: 'success',
                        });

                        console.log(response);
                    }
                })
            }
        })
    });
    $(document).on("click", ".removeProductID", function(){
        var id = $(this).attr("data-id");
        Swal.fire({
            title: ALIWOOLang.AreYouSure,
            text: ALIWOOLang.norevert,
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: ALIWOOLang.yesDelete
        }).then((result) => {
            if (result.value) {
                aewpreloader.show();
                $.ajax({
                    method : "post",
                    url : AEW_Data.adminAjaxURL+'?action=aew_remove_id_product',
                    data : {
                        post_id: id,
                    },
                    dataType: 'JSON',
                    success: function(response) {
                        aewpreloader.hide();
                        $(".remove_when_id").remove();
                        Swal.fire({
                            title: 'Ok',
                            text: ALIWOOLang.idProductDelete,
                            type: 'success',
                        });
                    },
                    error: function(response){
                        aewpreloader.hide();
                        Swal.fire({
                            title: ALIWOOLang.error,
                            text: ALIWOOLang.errorRemoveCategoryAE,
                            type: 'error',
                        });

                        console.log(response);
                    }
                })
            }
        })
    });

    $(document).on("click", ".getOrderAliExpress", function(){
        aewpreloader.show();
        var id = $(this).attr("data-id");
        var element = $(this);
        $.ajax({
            method : "post",
            url : AEW_Data.adminAjaxURL+'?action=aew_get_save_order',
            data : {
                idOrder: id,
            },
            dataType: 'JSON',
            success: function(response) {
                aewpreloader.hide();
                if(response['order'] > 0) {
                    element.parent("td").html('<a class="viewOrder" target="_blank" href="/wp-admin/post.php?post=' + response['order'] + '&action=edit">'+ALIWOOLang.viewOrder+'</a>');
                    element.remove();
                    Swal.fire({
                        title: ALIWOOLang.correct,
                        text: ALIWOOLang.orderImportOk,
                        type: 'success',
                    });
                }else{
                    Swal.fire({
                        title: 'Error',
                        text: ALIWOOLang.orderImportError,
                        type: 'error',
                    });
                }
            },
            error: function(response){
                aewpreloader.hide();
                Swal.fire({
                    title: 'Error',
                    text: ALIWOOLang.orderImportError,
                    type: 'error',
                });
                console.log(response);
            }
        })
    });

    $(document).on("click", ".getOrderDebug", function(){
        aewpreloader.show();
        var id = $(this).attr("data-id");
        var element = $(this);
        $.ajax({
            method : "post",
            url : AEW_Data.adminAjaxURL+'?action=aew_get_debug_order',
            data : {
                idOrder: id,
            },
            dataType: 'JSON',
            success: function(response) {
                aewpreloader.hide();
                Swal.fire({
                    title: 'DEBUG',
                    type: 'success',
                });
                console.log(response)
            },
            error: function(response){
                aewpreloader.hide();
                Swal.fire({
                    title: 'Error',
                    type: 'error',
                });
                console.log(response);
            }
        })
    });

    $(document).on("change", "input[name='aew_add_block_related']", function(){
        if($(this).prop('checked')) {
            $(".ae_show_only_related_block").show();
        }else{
            $(".ae_show_only_related_block").hide();
        }
    });
    $(document).on("click", "button.refreshAllJobs", function(){
        var element = $(".checkJob").not('.job-yes').not('.job-error');
        if(element.length == 0) {
            Swal.fire({
                title: 'Ops',
                text: ALIWOOLang.noJobsPending,
                type: 'error',
            });
            return;
        }
        $(".checkJob").not('.job-yes').not('.job-error').each(function(index, element){
            element.click();
        });
    });
    $(document).on("change", "select.change_aew_attr", function(){

        if(!AEW_Attributes) { console.error('No se encuentra el objeto'); return; }
        var key = $(this).val();
        var term = $(this).attr("data-key").replaceAll(" ", ".");

        $("select.select_terms_"+term).html('<option value="0">'+ALIWOOLang.chooseTerm+'</option>');
        awe_check_terms(term);
        $("select.select_terms_"+term).each(function(index) {
            var element = $("select.select_terms_"+term).eq(index);
            var nameSelect = element.attr('name').replace('[value]', '[value_ali]');
            var realTermName = element.attr("data-real-name");
            for (var termKey in AEW_Attributes[key]) {
                if (AEW_Attributes[key].hasOwnProperty(termKey)) {
                    var selected = strcasecmp(realTermName,AEW_Attributes[key][termKey]);
                    //console.log(realTermName + " es igual que " + AEW_Attributes[key][termKey] + " -> " + typeof selected + " ->> " +selected);
                    if(selected) {
                        var selectedOption = 'selected="selected"';

                    }else{
                        var selectedOption = '';
                    }
                    element.append('<option value="'+termKey+'" '+selectedOption+'>'+AEW_Attributes[key][termKey]+'</option>');
                    if(selectedOption != '') {
                        $("input[name='"+ nameSelect +"']").val(AEW_Attributes[key][termKey]);
                    }
                }
            }
        });

    });

    $(document).on("change", "select.changeTextValue_Ali", function(){
        var searchInput = $(this).attr("data-search-input");
        $("input[name='" + searchInput + "[value_ali]']").val($("option:selected", this).text());
    });

    //Features
    $(document).on("change", "select.change_aew_feature", function(){
        //console.log("CAMBIANDO VALOR");
        if(!AEW_Attributes) { console.error('No se encuentra el objeto'); return; }
        var key = $(this).val();
        var term = $(this).attr("data-key");
        // var finalData = Object.values(AEW_Attributes[key]);
        console.log(AEW_Attributes[key]);
        if(AEW_Attributes[key] == undefined) {
            $("select.select_terms_"+term).html('<option value="0">'+ALIWOOLang.chooseOption+'</option>');
            if($('span.explicationAliExpressUseValue').length > 0){
                $("span.explicationAliExpressUseValue").remove();
            }
            return;
        }
        if(AEW_Attributes[key].hasOwnProperty('type') && AEW_Attributes[key]['type'] =='string') {
            $("select.select_terms_"+term).html('<option value="AliExpressAlias">'+ALIWOOLang.useAlias+'</option>');
        }else{
            $("select.select_terms_"+term).html('<option value="0">'+ALIWOOLang.chooseTerm+'</option>');
            $("select.select_terms_"+term).each(function(index) {
                var element = $("select.select_terms_"+term).eq(index);
                var realTermName = element.attr("data-real-name");
                for (var termKey in AEW_Attributes[key]) {
                    if (AEW_Attributes[key].hasOwnProperty(termKey)) {
                        var selected = strcasecmp(realTermName,termKey);
                        console.log(realTermName + " es igual que " + termKey + " -> " + typeof selected + " ->> " +selected);
                        if(selected) {
                            var selectedOption = 'selected="selected"';
                        }else{
                            var selectedOption = '';
                        }
                        element.append('<option value="'+AEW_Attributes[key][termKey]+'" '+selectedOption+'>'+termKey+'</option>');
                    }
                }
            });
        }

    });

    $(document).on("change", ".select_term_feature", function(e){
        var value = $(this).val();
        var el = $(this);
        if(value == '4') {
            if(el.parent("td").eq(0).find('span.explicationAliExpressUseValue').length == 0){
                el.parent("td").eq(0).append('<span class="explicationAliExpressUseValue">'+ALIWOOLang.useValue+'</span>');
            }
        }else{
            if(el.parent("td").eq(0).find('span.explicationAliExpressUseValue').length > 0){
                console.log("Borrando elemento");
                el.parent("td").eq(0).find("span.explicationAliExpressUseValue").remove();
            }
        }
    });
    // $(document).on("change", "select#order_status", function(){
    //     var value = $(this).val();
    //     if(value == 'wc-ae-seller_part_send_goods') {
    //         $(".explication_partial_shipping").show();
    //         $("table.woocommerce_order_items tr").eq(0).prepend('<th class="aliexpress-partial-shipping" width="1%">'+ALIWOOLang.shipping+'</th>');
    //         $("tbody#order_line_items tr.item").each(function(index) {
    //             var idLine = $(this).attr("data-order_item_id");
    //             $("tbody#order_line_items tr.item").eq(index).prepend('<td class="aliexpress-partial-shipping" width="1%"><input type="checkbox" class="selectedProducts" name="aew_send[]" value="'+idLine+'"/></td>');
    //         });
    //     }else{
    //         $(".explication_partial_shipping").hide();            
    //         $("table.woocommerce_order_items tr th.aliexpress-partial-shipping").remove();
    //         $("tbody#order_line_items tr.item td.aliexpress-partial-shipping").remove();
    //     }
    // })
    $(document).on("change", "body.post-type-shop_order select[name='aew_carriers_sent']", function(){
        console.log("Cambiado");
        var expression = $("option:selected", this).attr("data-expression");
        console.log("Expression" + expression);
        $("input#aew_tracking_number").attr("data-pattern", expression);

        if($(this).val().substr(0, 5) == 'OTHER' ) {
            $("body.post-type-shop_order label.trackingURL").show();
            $("body.post-type-shop_order label.trackingURL input[name='aew_website_tracking']").prop('required',true);
        }else{
            $("body.post-type-shop_order label.trackingURL").hide();
            $("body.post-type-shop_order label.trackingURL input[name='aew_website_tracking']").prop('required',false);
        }
    });
    $(document).on("click", "body.post-type-shop_order .save_order", function(e){
        var statusOrder = $("select#order_status").val();
        // var ProductosAEnviar = $("input.selectedProducts:checked").length;
        // if(statusOrder == 'wc-ae-seller_part_send_goods' && ProductosAEnviar == 0) {
        //     e.stopPropagation();
        //     e.preventDefault();
        //     Swal.fire({
        //         title: ALIWOOLang.selectProducts,
        //         text: ALIWOOLang.selectProductsToSend,
        //         type: 'error',
        //     });
        //     return;
        // }

        console.log(statusOrder);
        if(statusOrder == 'wc-completed') {
            var carrier = $("body.post-type-shop_order select[name='aew_carriers_sent']").val();
            var trackingURL = $("body.post-type-shop_order label.trackingURL input[name='aew_website_tracking']");
            if(carrier.substr(0,5) == 'OTHER') {
                if(trackingURL.val() == '') {
                    e.stopPropagation();
                    e.preventDefault();
                    Swal.fire({
                        title: 'Ops',
                        text: ALIWOOLang.urlTrackingRequired,
                        type: 'error',
                    });
                    return;
                }
            }

            var code = $("input#aew_tracking_number");
            if(code.val() == '') {
                e.stopPropagation();
                e.preventDefault();
                Swal.fire({
                    title: 'Ops',
                    text: ALIWOOLang.TrakingRequire,
                    type: 'error',
                });
            }else{

                var pattern = new RegExp(code.attr("data-pattern"));
                if(!pattern.test(code.val())){
                    e.stopPropagation();
                    e.preventDefault();
                    Swal.fire({
                        title: 'Ops',
                        text: ALIWOOLang.TrakingIncorrect,
                        type: 'error',
                    });
                }
            }
        }
    });
    $(document).on("click", "input.useAlias", function(){
        var term = $(this).attr("term");
        console.log($(this).prop("checked"), term);
        if($(this).prop('checked')) {
            $("input[type='text'][term='"+term+"']").show();
        }else{
            $("input[type='text'][term='"+term+"']").hide();
        }
    });

    $(document).on("click", ".checkJob", function(){

        if($(this).hasClass("loading")) { return;}
        if($(this).hasClass("job-yes") || $(this).hasClass("job-error")) {
            Swal.fire({
                title: ALIWOOLang.jobFinished,
                text: ALIWOOLang.noUpdateJobFinished,
                type: 'error',
            });
            return;
        }
        aewpreloader.show();
        var idJob = $(this).attr("data-job");
        $("tr[data-job='"+idJob+"'] td.finished").html('...').addClass("loading");
        $.ajax({
            method : "post",
            url : AEW_Data.adminAjaxURL+'?action=aew_check_status_job',
            data : {
                job: idJob,
            },
            dataType: 'JSON',
            success: function(response) {
                aewpreloader.hide();
                console.log(response);
                $("tr[data-job='"+idJob+"']").removeClass('job-error job-yes job-processing');
                $("tr[data-job='"+idJob+"'] td.lastcheck").html(response['last_check']);
                $("tr[data-job='"+idJob+"'] td.totalproducts").html(response['total_item_count']);
                $("tr[data-job='"+idJob+"'] td.success").html(response['success_item_count']);
                $("tr[data-job='"+idJob+"'] td.finished").html(response['finished']).parent('tr').addClass(response['classFinished']).removeClass("loading");
            },
            error: function(response){
                aewpreloader.hide();
                console.log(response);
            }
        })
    });


    $(document).on("click", "a.removeInformationJobAew", function(){
        $("div.info-job-aliexpress").hide();
        aewpreloader.show();
        $.ajax({
            method : "post",
            url : AEW_Data.adminAjaxURL+'?action=aew_remove_notices_job',
            dataType: 'JSON',
            success: function(response) {
                aewpreloader.hide();
            },
            error: function(response){
                aewpreloader.show();
                console.log(response);
            }
        })
    });
    function strcasecmp(fString1, fString2) {

        var fString1 = fString1.replace(/[^A-Za-z0-9]+/g, '');
        var fString2 = fString2.replace(/[^A-Za-z0-9]+/g, '');
        var string1 = (fString1 + '').toLowerCase()
        var string2 = (fString2 + '').toLowerCase()

        if (string1 === string2) {
            return true
        }

        return false
    }

    $(document).on("change", "select.changeTextValue_Ali, select.select_term_feature", function(){
        var term = $(this).parent('td.selects_terms_attr').attr("data-key");
        console.log("CAMBIADO", term);
        awe_check_terms(term);
    });

    $("tr.ae_order").each(function(i, element) {
        console.log("PEDIDO AE ENCONTRADO");
        $("input[name='post[]']", element).prop('disabled', true);
    });

});

function filter_ae_products(tx){
    olist = Array.from(ae_products_cache).filter(function(el){ return el[1].subject.toLowerCase().includes(tx.toLowerCase())});
    list = [];
    jQuery( olist ).each(function(key, index){
        list.push(olist[key][0]);
    });
    return list;
}

function aew_delete_products(ids, reload = false) {
    var confirmado = true;
    if(reload) {
        confirmado = confirm(ALIWOOLang.AreYouSure);
    }

    if(confirmado) {
        if(reload) {
            aewpreloader.show();
        }
        console.log("ids", ids);
        jQuery.ajax({
            method : "post",
            url : AEW_Data.adminAjaxURL+'?action=aew_delete_confirm_ids_products',
            dataType: 'JSON',
            data: {
                products: ids
            },
            success: function(response) {
                if(reload) {
                    window.location.reload();
                }
                return response;
            },
            error: function(response){
                aewpreloader.show('ERROR');
                console.log(response);
            }
        })
    }
}

function aew_active_products(ids, reload = false) {
    var confirmado = true;
    if(reload) {
        confirmado = confirm(ALIWOOLang.AreYouSure);
    }

    if(confirmado) {
        if(reload) {
            aewpreloader.show();
        }
        console.log("ids", ids);
        jQuery.ajax({
            method : "post",
            url : AEW_Data.adminAjaxURL+'?action=aew_active_confirm_ids_products',
            dataType: 'JSON',
            data: {
                products: ids
            },
            success: function(response) {
                if(reload) {
                    window.location.reload();
                }
                return response;
            },
            error: function(response){
                aewpreloader.show('ERROR');
                console.log(response);
            }
        })
    }
}

function aew_disable_products(ids, reload = false) {
    var confirmado = true;
    if(reload) {
        confirmado = confirm(ALIWOOLang.AreYouSure);
    }

    if(confirmado) {
        if(reload) {
            aewpreloader.show();
        }
        console.log("ids", ids);
        jQuery.ajax({
            method : "post",
            url : AEW_Data.adminAjaxURL+'?action=aew_disable_confirm_ids_products',
            dataType: 'JSON',
            data: {
                products: ids
            },
            success: function(response) {
                if(reload) {
                    window.location.reload();
                }
                return response;
            },
            error: function(response){
                aewpreloader.show('ERROR');
                console.log(response);
            }
        })
    }
}

function awe_check_terms(term) {
    console.log("check terms", term);
    if(AEW_Data.duplicateAttributes == '1') { return; }
    if(!AEW_Attributes) { console.error('No se encuentra el objeto'); return; }
    var elements = jQuery('select.aew_check_this_values.select_terms_'+term);
    elements.removeClass('error').each(function () {
        console.log("term", term, this.value);
        var selectedValue = this.value;
        if(selectedValue == '0') { return; }
        elements
            .not(this)
            .filter(function() {
                //console.log([this.value, selectedValue]);
                if(this.value == selectedValue) {
                    Swal.fire({
                        title: ALIWOOLang.error,
                        text: ALIWOOLang.attrRepeat,
                        type: 'error',
                    });
                }
                return this.value == selectedValue;
            })
            .addClass('error');
    });
    if(jQuery('select.error').length > 0) {
        jQuery("input.endForm").prop('disabled', true);
    }else{
        jQuery('input.endForm').prop('disabled', false);
    }

}