<?php
/**
 * Login Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 4.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

do_action( 'woocommerce_before_customer_login_form' ); ?>

<section class="section accountlogin--section pageheader--pullup">
    <div class="container">
        <div class="accountlogin">
		<?php if(is_page('login')){ ?>
		<h2 class="heading-title">Login or <a href="<?php echo esc_url( get_page_link( 408 ) ); ?>">create an account</a></h2>
		<?php }elseif(is_page('register')){ ?>
			<h2 class="heading-title">Register or <a href="<?php echo esc_url( get_page_link( 406 ) ); ?>">Login to account</a></h2>
		<?php } ?>

            <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>

            <div class="u-columns col2-set" id="customer_login">

                <div class="u-column1 col-1">

                    <?php endif; ?>


                    <form class="woocommerce-form woocommerce-form-login login" method="post">

                        <?php do_action( 'woocommerce_login_form_start' ); ?>
                        <div class="formfield woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                            <label class="is-required"
                                for="username"><?php esc_html_e( 'Username or email address', 'woocommerce' ); ?></label>
                            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text"
                                name="username" id="username" autocomplete="username"
                                value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required/><?php // @codingStandardsIgnoreLine ?>
                        </div>
                        <div class="formfield woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                            <label class="is-required" for="password"><?php esc_html_e( 'Password', 'woocommerce' ); ?></label>
                            <input class="woocommerce-Input woocommerce-Input--text input-text" type="password"
                                name="password" id="password" autocomplete="current-password" />
                        </div>
						<!-- /<div class="woocommerce-LostPassword lost_password"> -->
                            <a class="forgot-password"
                                href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgotten password?', 'woocommerce' ); ?></a>
                        <!-- </div> -->
                        <?php do_action( 'woocommerce_login_form' ); ?>
                        <div class="formfield formfield--submit form-row">
						<button type="submit" class="btn btn-orange woocommerce-button button woocommerce-form-login__submit"
                                name="login"
                                value="<?php esc_attr_e( 'Log in', 'woocommerce' ); ?>"><?php esc_html_e( 'Log in', 'woocommerce' ); ?></button>
							<div class="checkbox">
								<label
                                class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme">
                                <input class="woocommerce-form__input woocommerce-form__input-checkbox"
                                    name="rememberme" type="checkbox" id="rememberme" value="forever" />
                                <span><?php esc_html_e( 'Remember me', 'woocommerce' ); ?></span>
                            </label>
							</div>
                            <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
                           
						</div>

                        <?php do_action( 'woocommerce_login_form_end' ); ?>

                    </form>

                    <?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>

                </div>

                <div class="u-column2 col-2">

                    <form method="post" class="woocommerce-form woocommerce-form-register register"
                        <?php do_action( 'woocommerce_register_form_tag' ); ?>>

                        <?php do_action( 'woocommerce_register_form_start' ); ?>

                        <?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

                        <div class="formfield woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                            <label class="is-required" for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?></label>
                            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text"
                                name="username" id="reg_username" autocomplete="username"
                                value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required/><?php // @codingStandardsIgnoreLine ?>
						</div>

                        <?php endif; ?>

                        <div class="formfield woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                            <label class="is-required" for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?></label>
                            <input type="email" class="woocommerce-Input woocommerce-Input--text input-text"
                                name="email" id="reg_email" autocomplete="email"
                                value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" /><?php // @codingStandardsIgnoreLine ?>
						</div>

                        <?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

                        <div class="formfield woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
                            <label class="is-required" for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?></label>
                            <input type="password" class="woocommerce-Input woocommerce-Input--text input-text"
                                name="password" id="reg_password" autocomplete="new-password" />
						</div>

                        <?php else : ?>

                        <p><?php esc_html_e( 'A link to set a new password will be sent to your email address.', 'woocommerce' ); ?>
                        </p>

                        <?php endif; ?>

                        <?php do_action( 'woocommerce_register_form' ); ?>

                        <p class="woocommerce-form-row form-row">
                            <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
                            <button type="submit"
                                class="btn btn-orange woocommerce-Button woocommerce-button button woocommerce-form-register__submit"
                                name="register"
                                value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
                        </p>

                        <?php do_action( 'woocommerce_register_form_end' ); ?>

                    </form>

                </div>

            </div>
            <?php endif; ?>
            <!-- <h2 class="heading-title">Login or <a href="#">create an account</a></h2>
            <form action="">
                <div class="formfield">
                    <label class="is-required">Username or email address</label>
                    <input type="text" required>
                </div>
                <div class="formfield">
                    <label class="is-required">Password</label>
                    <input type="password" value="" required>
                </div>
                <a href="#" class="forgot-password">Forgotten password?</a>
                <div class="formfield formfield--submit">
                    <button class="btn btn-orange" type="submit">Login</button>
                    <div class="checkbox">
                        <input id="rememberMe" type="checkbox" name="rememberMe" value="Remember Me">
                        <label for="rememberMe">Remember Me</label>
                    </div>
                </div>
            </form> -->
        </div>
    </div>
</section>




<?php do_action( 'woocommerce_after_customer_login_form' ); ?>