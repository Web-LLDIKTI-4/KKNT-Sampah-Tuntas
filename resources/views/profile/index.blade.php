@extends('layouts.app')
@section('title','Data Profil')
@section('container')

<p id="resultcontent">Loading data...</p>    

<script>
$(function(){
    $('#modalku').on('show.bs.modal', function (e) {
        $(".modal-dialog").addClass('modal-lg');
    })
    $("#resultcontent").load("{{ url(Auth::user()->role == 'mahasiswa' ? 'mhsprofile/data' : 'profile/data') }}");
   
    $("body").on("change","#form-file-upload",function(e){
        e.preventDefault();
        var formData = new FormData($(this)[0]);
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var btnHtml = $("#btnSubmit_"+id+"").html();		
		  $.ajax({
			  url: action,
              dataType:'json',
              type:'post',
			  data: formData,
			  processData: false, // important
			  contentType: false, // important
			  beforeSend:function(){					
				  $("#btnSubmit_"+id+"").prop("disabled",true);
				  $("#btnSubmit_"+id+"").html("<span class='spinner-grow spinner-grow-sm' role='status'></span> loading...");			
			  },
			  complete:function(){
				  $("#btnSubmit_"+id+"").prop("disabled",false);
				  $("#btnSubmit_"+id+"").html(btnHtml);	
			  },
			  success: function(ret) {
				    if(ret.success == true){
              toastr.success(ret.message)		
            }else{
              if (ret.hasOwnProperty('errors')) {
                              // Ada kesalahan validasi
                      var errors = ret.errors;

                      // Menghapus pesan error sebelumnya
                      $('.errors-message').remove();

                      // Menampilkan pesan error pada setiap field
                      $.each(errors, function(key, value) {
                          var inputField = $('[name="' + key + '"]');
                          inputField.after('<span class="errors-message text-danger">' + value[0] + '</span>');
                          // Menambahkan event listener untuk menghapus pesan error saat field mendapatkan fokus
                          inputField.on('focus', function(){
                                  $(this).siblings('.errors-message').remove();
                              });
                      });
                  }
              toastr.warning(ret.message)	
            }
			  },
			  error:function(xhr,ajaxOptions,thrownError){
				  console.log(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);
                  toastr.error(thrownError)				
			  }	

		  });
	});
    $("body").on("submit","#form-update,#form-updatepassword",function(){       
        var action = $(this).attr("action");
        var id = $(this).attr("id");
        var btnHtml = $("#btnSubmit_"+id+"").html();
        var dString = $(this).serialize();        
        $.ajax({
            dataType:'json',
            type:'post',
            url:action,
            data:dString,
            beforeSend:function(){
                $("#btnSubmit_"+id+"").prop("disabled",true);
                $("#btnSubmit_"+id+"").html("<span class='spinner-grow spinner-grow-sm' role='status' aria-hidden='true'></span> Loading...");			
            },
            complete:function(){
                $("#btnSubmit_"+id+"").prop("disabled",false);
                $("#btnSubmit_"+id+"").html(btnHtml);	
            },
            success:function(ret){
                if(ret.success == true){
                  toastr.success(ret.message)		
                }else{
                    if (ret.hasOwnProperty('errors')) {
                                // Ada kesalahan validasi
                        var errors = ret.errors;

                        // Menghapus pesan error sebelumnya
                        $('.errors-message').remove();

                        // Menampilkan pesan error pada setiap field
                        $.each(errors, function(key, value) {
                            var inputField = $('[name="' + key + '"]');
                            inputField.after('<span class="errors-message text-danger">' + value[0] + '</span>');
                            // Menambahkan event listener untuk menghapus pesan error saat field mendapatkan fokus
                            inputField.on('focus', function(){
                                    $(this).siblings('.errors-message').remove();
                                });
                        });
                    }
                    toastr.warning(ret.message)	
                }
            },
            error:function(xhr,ajaxOptions,thrownError){
                console.log(xhr.status+"\n"+xhr.responseText+"\n"+thrownError);				
            }			
            
        });
        return false;
    });
})
</script>
@stop 