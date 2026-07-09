<link rel="stylesheet" href="<?= MVNIME_URL . 'assets/bootstrap.min.css' ?>">
<link rel="stylesheet" href="<?= MVNIME_URL . 'assets/muvigrabber.css' ?>">
<link rel="stylesheet" href="<?= MVNIME_URL . 'assets/style.css' ?>">
<div class="wrap">
    <h1 class="text-slate-800 text-[2rem] leading-10 pb-3 font-semibold text-left wp-heading-inline">Muvi Grabber</h1>
    <hr class="wp-header-end">
    <main>
        <div class="max-w-3xl mx-auto px-6 py-3 lg:max-w-7xl bg-white rounded d-none d-md-block">
            <form action="<?= esc_html(admin_url('admin-post.php')); ?>" method="POST">
                <input type="hidden" name="action" value="muvinime_save_settings">
                <?php wp_nonce_field('mvnime_ajax_nonce175', 'mvnime_nonce'); ?>
                <section class="border-b border-solid border-slate-200 px-8 py-8 justify-between">
                    <div class="mr-16 w-full flex items-center">
                        <h3 class="p-0 flex-1 justify-right inline-flex text-xl leading-6 font-semibold text-slate-800">API Key</h3>
                        <input type="text" id="apikey" name="apikey" autocomplete="off" value="<?= $key ?>" style="min-width:50%">
                    </div>
                    <p class="mt-2 w-9/12 text-sm text-slate-500 tablet:w-full">Masukkan API Key Anda</p>
                </section>
                <section class="border-b border-solid border-slate-200 px-8 py-8 justify-between">
                    <div class="mr-16 w-full flex items-center">
                        <h3 class="p-0 flex-1 justify-right inline-flex text-xl leading-6 font-semibold text-slate-800">ID Author</h3>
                        <input type="number" id="userid" name="userid" autocomplete="off" value="<?= $userid ?>" style="min-width:50%">
                    </div>
                    <p class="mt-2 w-9/12 text-sm text-slate-500 tablet:w-full">Masukkan User ID dari author</p>
                </section>
                <section class="border-b border-solid border-slate-200 px-8 py-8 justify-between">
                    <div class="mr-16 w-full flex items-center">
                        <h3 class="p-0 flex-1 justify-right inline-flex text-xl leading-6 font-semibold text-slate-800">Proxy URL</h3>
                        <input type="text" id="proxy_url" name="proxy_url" autocomplete="off" value="<?= $proxy_url ?>" style="min-width:50%">
                    </div>
                    <p class="mt-2 w-9/12 text-sm text-slate-500 tablet:w-full">Proxy untuk download gambar (format: <code>http://user:pass@host:port</code>)</p>
                </section>
                <section class="border-b border-solid border-slate-200 px-8 py-8 justify-between">
                    <div class="mr-16 w-full flex items-center">
                        <h3 class="p-0 flex-1 justify-right inline-flex text-xl leading-6 font-semibold text-slate-800">Grab API URL</h3>
                        <input type="url" id="grab_api_url" name="grab_api_url" autocomplete="off" value="<?= $grab_api_url ?>" style="min-width:50%">
                    </div>
                    <p class="mt-2 w-9/12 text-sm text-slate-500 tablet:w-full">Base URL untuk Grab API</p>
                </section>
                <section class="border-b border-solid border-slate-200 px-8 py-8 justify-between">
                    <div class="mr-16 w-full flex items-center">
                        <h3 class="p-0 flex-1 justify-right inline-flex text-xl leading-6 font-semibold text-slate-800">Player Base URL</h3>
                        <input type="url" id="player_base_url" name="player_base_url" autocomplete="off" value="<?= $player_base_url ?>" style="min-width:50%">
                    </div>
                    <p class="mt-2 w-9/12 text-sm text-slate-500 tablet:w-full">Base URL untuk player streaming (contoh: <code>https://playsobat.xyz</code>)</p>
                </section>
                <section class="border-b border-solid border-slate-200 px-8 py-8 justify-between">
                    <div class="mr-16 w-full flex items-center">
                        <h3 class="p-0 flex-1 justify-right inline-flex text-xl leading-6 font-semibold text-slate-800">Logging</h3>
                        <select name="enable_log" id="enable_log" style="min-width:50%">
                            <option value="" disabled <?php if (empty($enable_log)) echo 'selected' ?>>- Pilih -</option>
                            <option value="1" <?php if ($enable_log == 1) echo 'selected' ?>>Aktifkan</option>
                            <option value="0" <?php if ($enable_log == 0) echo 'selected' ?>>Matikan</option>
                        </select>
                    </div>
                    <p class="mt-2 w-9/12 text-sm text-slate-500 tablet:w-full">Log aktivitas bot</p>
                </section>
                <?php if ($enable_log): ?>
                <section class="border-b border-solid border-slate-200 px-8 py-8 justify-between">
                    <div class="mr-16 w-full flex items-center">
                        <h3 class="p-0 flex-1 justify-right inline-flex text-xl leading-6 font-semibold text-slate-800">Log Level</h3>
                        <select name="log_level" id="log_level" style="min-width:50%">
                            <option value="DEBUG" <?php if ($log_level == 'DEBUG') echo 'selected' ?>>Debug</option>
                            <option value="INFO" <?php if ($log_level == 'INFO') echo 'selected' ?>>Info</option>
                            <option value="WARNING" <?php if ($log_level == 'WARNING') echo 'selected' ?>>Warning</option>
                            <option value="ERROR" <?php if ($log_level == 'ERROR') echo 'selected' ?>>Error</option>
                        </select>
                    </div>
                    <p class="mt-2 w-9/12 text-sm text-slate-500 tablet:w-full">
                        Level minimal pesan yang dicatat. Semakin rendah level, semakin banyak detail.<br>
                        <a class="font-semibold" href="<?= $log_path ?>" target="_blank">Lihat log</a>
                    </p>
                </section>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary btn-sm mt-3 px-4 py-2 text-sm font-medium rounded-md shadow-sm text-white">Simpan perubahan</button>
            </form>
        </div>
        <div class="max-w-3xl mx-auto px-6 py-3 lg:max-w-7xl bg-white rounded d-sm-block d-md-none">
            <p class="text-slate-800 text-lg font-semibold text-center">Fitur ini hanya tersedia pada mode landscape</p>
        </div>
    </main>
</div>