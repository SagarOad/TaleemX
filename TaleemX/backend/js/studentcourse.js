    function addtocart(id)
    {        
        $.ajax({
            type:'POST',
            url: baseurl+'cart/addcart',
            data:{id: id},
             dataType: 'json',
            success: function (data) {
              if (data.status == "fail") {
                        toastr.error(data.error);
                    } else {
                        toastr.success(data.message);
                    }
            }
        }); 
    }

    function removecartheader(rowid)
    {
        
        $.ajax({
            type:'POST',
            url: base_url+'cart/removecartheader',
            data:{rowid: rowid},
            success: function (data) {               
                // window.location.reload(true);
            }
        });
    }