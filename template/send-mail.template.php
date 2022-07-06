	<div class="send-mail">
		<i class="fa fa-times" id="send-mail-close"></i>
		<?php if ($config['mail']['send_all_later']): ?>
		    <p data-i18n="insertMailToDB"></p>
		<?php else: ?>
		    <p data-i18n="insertMail"></p>
		<?php endif; ?>
		<form id="send-mail-form" style="margin: 0;">
			<input class="mail-form-input" size="35" type="email" name="sendTo">
			<input id="mail-form-image" type="hidden" name="image" value="">

			<?php if ($config['mail']['send_all_later']): ?>
				<input type="checkbox" id="mail-form-send-link" name="send-link" value="yes">
				<label data-i18n="sendAllMail" for="mail-form-send-link"></label>
				<button class="mail-form-input btn rotaryfocus" name="submit" type="submit" value="Send"><span data-i18n="add"></span></button>
			<?php else: ?>
				<button class="mail-form-input btn rotaryfocus" name="submit" type="submit" value="Send"><span data-i18n="send"></span></button>
			<?php endif; ?>

		</form>

		<div id="mail-form-message" style="max-width: 75%"></div>
	</div>
