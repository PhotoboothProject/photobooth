<div class="w-full max-w-xl h-144 rounded-lg p-8 bg-white flex flex-col shadow-xl">
    <form method="post">

        <div class="w-full flex flex-col items-center justify-center text-2xl font-bold text-brand-1 mb-2">
            <?=$config['ui']['branding']?> Login
        </div>

        <div class="w-full text-center text-gray-500 mb-8">
            <span data-i18n="login_please"></span>
        </div>

        <!-- user -->
        <div class="relative">
            <label class="<?=$labelClass?>" for="username"><span data-i18n="login_username"></span></label>
            <input class="<?=$inputClass?>" type="text" name="username" id="username" autocomplete="on" required>
        </div>

        <!-- pw -->
        <div class="relative mt-2">
            <label class="<?=$labelClass?>" for="password"><span data-i18n="login_password"></span></label>
            <input class="<?=$inputClass?>"  type="password" name="password" id="password" autocomplete="on" required>
            <span toggle="#password" class="absolute w-10 h-10 bottom-0 right-0 cursor-pointer text-brand-1 flex items-center justify-center password-toggle <?=$config['icons']['password_visibility']?>"></span>
        </div>

        <!-- btn -->
        <div class="mt-6">
            <input class="<?=$btnClass?>" type="submit" name="submit" value="Login">
        </div>
        <?php if ($error !== false) {
            echo '<span class="w-full flex mt-6 text-red-500" data-i18n="login_invalid"></span>'; 
        } ?>  
    </form>
</div>
<div class="w-full max-w-xl my-12 border-b border-solid border-white border-opacity-20"></div>
