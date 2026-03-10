<h3><?php echo $title ?></h3>
<div class="table">
    <form action="#" method="post" autocomplete="on">
        <div class="row">
            <label class="label">A number between 0 and <?php echo $max ?>:</label>
            <input type="text" class="lc-form-input" disabled value="<?php echo $randomNumber ?>"> (generating on every render)
        </div>

        <div class="row">
            <label class="label">Counter: <strong><?php echo $count ?></strong></label>
            <div>
                <button type="button" class="button red mini" data-live="decrement">Decrement--</button>
                <button type="button" class="button green mini" data-live="increment">Increment++</button>
            </div>
        </div>

        <div class="row">
            <label for="name" class="label">Full name</label>
            <input data-bind="name" id="name" name="name" type="text" class="lc-form-input fluid-25" value="<?php echo $name ?>" placeholder="Enter your name" />
            <?php echo $name ?>
        </div>

        <div class="row">
            <div class="label">Volume (0–100): <?php echo $volume ?> selected</div>
            <input data-bind="volume" id="volume" name="volume" type="range" class="lc-form-input fluid-25" min="0" max="100" value="<?php echo $volume ?>" />
        </div>

        <div class="row">
            <label for="birthdate" class="label">Birthdate</label>
            <input data-bind="birthdate" id="birthdate" name="birthdate" type="date" class="lc-form-input fluid-25" value="<?php echo $birthdate ?>" />
            <?php echo $birthdate ?>
        </div>

        <div class="row">
            <label for="meeting_time" class="label">Meeting time</label>
            <input data-bind="meeting_time" id="meeting_time" name="meeting_time" type="time" class="lc-form-input fluid-25" value="<?php echo $meeting_time ?>" />
            <?php echo $meeting_time ?>
        </div>

        <div class="row">
            <label for="favorite_color" class="label">Favorite color</label>
            <input data-bind="favorite_color" id="favorite_color" name="favorite_color" type="color" value="<?php echo $favorite_color ?>" />
            <?php echo $favorite_color ?>
        </div>

        <div class="row">
            <div class="label">Bio</div>
            <textarea data-bind="bio" data-on="keyup" id="bio" name="bio" class="lc-form-input fluid-50" rows="4" placeholder="Write something..."><?php echo $bio ?></textarea>
            <div><?php echo $bio; ?></div>
        </div>

        <div class="row">
            <label for="country" class="label">Country</label>
            <select data-bind="country" id="country" name="country" class="lc-form-input fluid-25">
                <option value="">-- Select --</option>
                <option value="us">United States</option>
                <option value="ca">Canada</option>
                <option value="uk">United Kingdom</option>
                <option value="au">Australia</option>
            </select>
            <?php echo 'selected: ' . $country; ?>
        </div>

        <div class="row">
            <div class="label">Skills</div>
            <select data-bind="skills[]" id="skills" name="skills[]" class="lc-form-input fluid-25" multiple="multiple" size="5">
                <option value="php">PHP</option>
                <option value="js">JavaScript</option>
                <option value="sql">SQL</option>
                <option value="css">CSS</option>
                <option value="devops">DevOps</option>
            </select>
            <?php echo 'selected: ' . (implode(', ', $skills)) . '<br>'; ?>
        </div>

        <div class="row">
            <label class="label">Contact preference: (<?php echo $contact_pref; ?>)</label>
            <div class="form-check">
                <input data-bind="contact_pref" id="contact_email" name="contact_pref" type="radio" class="form-check-input" value="email" checked="checked" />
                <label for="contact_email" class="form-check-label">Email</label>
            </div>
            <div class="form-check">
                <input data-bind="contact_pref" id="contact_phone" name="contact_pref" type="radio" class="form-check-input" value="phone" />
                <label for="contact_phone" class="form-check-label">Phone</label>
            </div>
            <div class="form-check">
                <input data-bind="contact_pref" id="contact_sms" name="contact_pref" type="radio" class="form-check-input" value="sms" />
                <label for="contact_sms" class="form-check-label">SMS</label>
            </div>
        </div>

        <div class="row">
            <strong>Food:</strong> <?php echo  $food ? '(' . (implode(', ', $food)) . ')' : ''; ?>
            <div class="form-check">
                <input data-bind="food[]" id="food-pizza" name="food[]" type="checkbox" class="form-check-input" value="pizza" />
                <label for="food-pizza" class="form-check-label">Pizza</label>
            </div>
            <div class="form-check">
                <input data-bind="food[]" id="food-tacos" name="food[]" type="checkbox" class="form-check-input" value="tacos" />
                <label for="food-tacos" class="form-check-label">Tacos</label>
            </div>
            <div class="form-check">
                <input data-bind="food[]" id="food-sushi" name="food[]" type="checkbox" class="form-check-input" value="sushi" />
                <label for="food-sushi" class="form-check-label">Sushi</label>
            </div>
        </div>

        <div class="row">
            <div class="form-check">
                <input data-bind="subscribe" id="subscribe" name="subscribe" type="checkbox" class="form-check-input" checked="checked" />
                <label for="subscribe" class="form-check-label">Subscribe to newsletter</label>
            </div>
            <div class="form-check">
                <input data-bind="terms" id="terms" name="terms" type="checkbox" class="form-check-input" />
                <label for="terms" class="form-check-label">I agree to the terms</label>
            </div>
        </div>

        <button type="button" class="button" data-live="render" <?php echo empty($terms) ? 'disabled' : '' ?>>Live Button</button>
    </form>
</div>
