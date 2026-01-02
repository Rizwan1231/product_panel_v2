<input type="hidden" class="placeholder_image" value="../panel-assets/img/placeholder.png">
<input type="hidden" id="base_url" value="<?= base_url(); ?>">
<input type="hidden" id="site_url" value="<?= base_url(); ?>">
<script src="../panel-assets/plugins/jquery/jquery-3.6.0.min.js"></script>
<script src="../panel-assets/plugins/popperjs/popper.min.js"></script>
<script src="../panel-assets/plugins/bootstrap/bootstrap.min.js"></script>
<script src="../panel-assets/plugins/nicescroll/jquery.nicescroll.min.js"></script>
<script src="../panel-assets/plugins/sweetalert/sweetalert2.all.min.js"></script>
<script src="../panel-assets/plugins/jqueryvalidation/jquery.validate.min.js"></script>
<script src="../panel-assets/plugins/selectric/jquery.selectric.min.js"></script>
<script src="../panel-assets/plugins/select2/select2.min.js"></script>
<script src="../panel-assets/plugins/chatjs/Chart.min.js"></script>
<!--<script src="../panel-assets/custom/user/dashboard.js"></script>
<script src="../panel-assets/plugins/dropzone/dropzone.min.js"></script>
<script src="../panel-assets/custom/media.js"></script>-->
<script src="../panel-assets/plugins/summernote/summernote-bs4.js"></script>
<script src="../panel-assets/plugins/summernote/summernote.js"></script>
<script src="../panel-assets/plugins/cropperjs/cropper.min.js"></script>
<script src="../panel-assets/plugins/cropperjs/jquerycropper.min.js"></script>
<script src="../panel-assets/custom/user/coverphoto.js"></script>
<script src="../panel-assets/custom/user/editproduct.js"></script>
<script src="../panel-assets/js/jquery.dataTables.min.js"></script>
<script src="../panel-assets/js/dataTables.bootstrap4.min.js"></script>
<script src="../panel-assets/plugins/clipboard-js/clipboard.min.js"></script>
<script>
        "use strict";
        var clipboard = new ClipboardJS('.clipboard-button');

        clipboard.on('success', function(e) {
            Sweet('success', 'Copied to clipboard')
            e.clearSelection();
        });
    </script>
<script>
        "use strict";
        var x = 0; //Initial field counter is 1
        var count = 1;
        var maxField = 10; //Input fields increment limitation
        var addButton = $('.add_button'); //Add button selector
        var wrapper = $('.field_wrapper'); //Input field wrapper

        //Once add button is clicked
        $(addButton).on('click', function() {
            //Check maximum number of input fields
            if (x < maxField) {
                //Increment field counter
                var fieldHTML = `<div class='row'><div class="col-md-5">
                      <br>
                      <input type="text" required class="form-control" name="inputs[${count}][label]" value="" placeholder="Label here">
                      </div>
                      <div class="col-md-6">
                        <br>
                          <select class="form-control" name="inputs[${count}][type]" id="">
                               <option value="text">Text</option>
                               <option value="number">Number</option>
                               <option value="textarea">Textarea</option>
                               <option value="email">Email</option>
                           </select>
                      </div>
                      <div class="col-md-1">
                          <a href="javascript:void(0);" class="remove_button text-xxs mr-2 btn btn-danger mb-0 btn-sm mt-4 text-xxs" title="Add field">
                              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z"/>
                              </svg>
                          </a>
                      </div><div>`; //New input field html
                x++;
                count++;
                $(wrapper).append(fieldHTML); //Add field html
            }
        });

        //Once remove button is clicked
        $(wrapper).on('click', '.remove_button', function(e) {
            e.preventDefault();
            $(this).parent('div').parent('div.row').remove(); //Remove field html
            x--; //Decrement field counter
        });

$('.datatables').DataTable( {
    responsive: true,
    order: [[0, 'desc']]
} );
    </script>
<script>
var modal = document.querySelector(".modal");
var trigger = document.querySelector(".trigger");
var closeButton = document.querySelector(".close-button");

function toggleModal() {
    modal.classList.toggle("show-modal");
    if (modal.classList.contains("show-modal")) {
        $(".main-sidebar, .navbar, .main-content, .main-footer").css("z-index", "1");
    } else {
        $(".main-sidebar, .navbar, .main-content, .main-footer").css("z-index", "");
    }
}

function windowOnClick(event) {
    if (event.target === modal) {
        toggleModal();
    }
}
closeButton.addEventListener("click", toggleModal);
window.addEventListener("click", windowOnClick);

$('.btn-topup').on('click', function()
{
   toggleModal();
});
</script>
<script src="../panel-assets/js/scripts.js"></script>
<script src="../panel-assets/js/main.js"></script>
<script src="../panel-assets/js/custom.js?v=2.1"></script>
<script src="../panel-assets/custom/form.js"></script>
</body>
</html>
