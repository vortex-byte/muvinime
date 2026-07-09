jQuery(document).ready(function ($) {
    function callAjax(params = null) {
        let url = muvigrabber.ajax_url,
            body = {};

        body = {
            security: muvigrabber.ajax_nonce,
            action: 'send_form',
            link: params.link,
            type: params.type,
        }

        return new Promise((resolve, reject) => {
            jQuery.ajax({
                type: 'POST',
                url: url,
                data: body,
                cache: false,
                async: true,
                success: function (response) {
                    resolve(response);
                },
                error: function (err) {
                    alert('There is some error. Please check browser console');
                    console.error(err);
                    reject(err);
                }
            });
        })
    }

    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (err) {
            return false;
        }
    }

    $('#update').click(function () {
        let postType = $('#post-type').val(),
            url = $('#url').val();

        $(this).attr('disabled', 1);

        if (postType && url && isValidUrl(url)) {
            callAjax({
                type: postType,
                link: url,
            }).then(
                (data) => {
                    if (data.status === 'success') {
                        if (Array.isArray(data.stream) && data.stream.length > 0) {
                            data.stream.forEach((st, i) => {
                                if (i <= 8) {
                                    $(`#opsi-title-player${i}`).val(st.player);
                                    $(`#opsi-player${i}`).val(st.link);
                                }
                            });
                        }

                        if (Array.isArray(data.download) && data.download.length > 0) {
                            data.download.forEach((dl, i) => {
                                if (i <= 8) {
                                    $(`#opsi-title-download${i}`).val(dl.server);
                                    $(`#opsi-download${i}`).val(dl.link);
                                }
                            });
                        }

                        alert('Berhasil');
                    } else {
                        console.log(data);
                        alert('Gagal mendapatkan data');
                    }

                    $('#update').attr('disabled', 0);
                }
            ).catch((err) => {
                alert('Gagal terhubung ke server API');
                $('#update').attr('disabled', 0);
                console.error(err.message);
            });
        } else {
            alert('Masukkan post type, dan link');
        }
    })
});