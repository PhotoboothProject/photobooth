<div class="stages rotarygroup" id="result">
    <div class="resultInner hidden">

        <?php if ($config['button']['homescreen']): ?>
            <a href="#" class="<?php echo $btnClass; ?> homebtn rotaryfocus"><i class="<?php echo $config['icons']['home']; ?>"></i> <span
                        data-i18n="home"></span></a>
        <?php endif; ?>

        <?php if ($config['ui']['result_buttons']): ?>
            <?php if ($config['video']['enabled']): ?>
                <?php if ($config['video']['qr']): ?>
                    <img src="" id="resultVideoQR" alt="video qr code">
                <?php endif; ?>
                <?php if ($config['video']['gif']) { ?>
                    <img id="resultVideo" src="" alt="result gif">
                <?php } else { ?>
                    <video id="resultVideo" autoplay loop muted>
                    </video>
                <?php } ?>
            <?php endif; ?>

            <?php if ($config['gallery']['enabled']): ?>
                <a href="#" class="<?php echo $btnClass; ?> gallerybtn rotaryfocus"><i class="<?php echo $config['icons']['gallery']; ?>"></i> <span data-i18n="gallery"></span></a>
            <?php endif; ?>

            <?php if ($config['qr']['enabled']): ?>
                <a href="#" class="<?php echo $btnClass; ?> qrbtn rotaryfocus"><i class="<?php echo $config['icons']['qr']; ?>"></i> <span
                            data-i18n="qr"></span></a>
            <?php endif; ?>

            <?php if ($config['mail']['enabled']): ?>
                <a href="#" class="<?php echo $btnClass; ?> mailbtn rotaryfocus"><i class="<?php echo $config['icons']['mail']; ?>"></i> <span
                            data-i18n="mail"></span></a>
            <?php endif; ?>

            <?php if ($config['print']['from_result']): ?>
                <a href="#" class="<?php echo $btnClass; ?> printbtn rotaryfocus"><i class="<?php echo $config['icons']['print']; ?>"></i> <span
                            data-i18n="print"></span></a>
            <?php endif; ?>

            <?php if (!$config['button']['force_buzzer']): ?>
                <?php if ($config['picture']['enabled']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> newpic rotaryfocus"><i class="<?php echo $config['icons']['take_picture']; ?>"></i> <span
                                data-i18n="newPhoto"></span></a>
                <?php endif; ?>

                <?php if ($config['custom']['enabled']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> newcustom rotaryfocus"><i class="<?php echo $config['icons']['take_custom']; ?>"></i>
                        <span><?php echo $config['custom']['btn_text']; ?></span></a>
                <?php endif; ?>

                <?php if ($config['collage']['enabled']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> newcollage rotaryfocus"><i class="<?php echo $config['icons']['take_collage']; ?>"></i>
                        <span
                                data-i18n="newCollage"></span></a>
                <?php endif; ?>

                <?php if ($config['video']['enabled']): ?>
                    <a href="#" class="<?php echo $btnClass; ?> newVideo rotaryfocus"><i class="<?php echo $config['icons']['take_video']; ?>"></i> <span
                                data-i18n="newVideo"></span></a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($config['filters']['enabled']): ?>
                <a href="#" class="<?php echo $btnClass; ?> imageFilter rotaryfocus"><i class="<?php echo $config['icons']['filter']; ?>"></i> <span
                            data-i18n="selectFilter"></span></a>
            <?php endif; ?>

            <?php if ($config['picture']['allow_delete']): ?>
                <a href="#" class="<?php echo $btnClass; ?> deletebtn <?php if ($config['delete']['no_request']) {
     echo 'rotaryfocus';
 } ?> "><i class="<?php echo $config['icons']['delete']; ?>"></i> <span data-i18n="delete"></span></a>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php if ($config['qr']['enabled']): ?>
        <div id="qrCode" class="modal">
            <div class="modal__body <?php echo $uiShape; ?>"></div>
        </div>
    <?php endif; ?>
</div>