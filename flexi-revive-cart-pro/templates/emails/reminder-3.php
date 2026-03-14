<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset( $email_subject ) ? esc_html( $email_subject ) : esc_html__( 'A special offer for you', 'flexi-revive-cart-pro' ); ?></title>
</head>
<body style="margin:0;padding:0;background:#f7f7f7;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f7f7;padding:20px 0;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:100%;">

        <!-- Header -->
        <tr>
          <td style="background:#46b450;padding:28px 32px;text-align:center;">
            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">{store_name}</h1>
          </td>
        </tr>

        <!-- Discount badge -->
        <tr>
          <td style="background:#edfbee;padding:20px 32px;text-align:center;">
            <p style="margin:0;font-size:28px;font-weight:700;color:#00a32a;">🎁 {discount_amount} OFF</p>
            <p style="margin:6px 0 0;font-size:14px;color:#555;"><?php esc_html_e( 'Exclusive discount just for you – limited time offer!', 'flexi-revive-cart-pro' ); ?></p>
          </td>
        </tr>

        <!-- Hero -->
        <tr>
          <td style="padding:32px 32px 0;text-align:center;">
            <h2 style="font-size:22px;color:#1e1e1e;margin:0 0 8px;"><?php esc_html_e( 'Last chance to complete your purchase!', 'flexi-revive-cart-pro' ); ?></h2>
            <p style="font-size:15px;color:#555;margin:0;"><?php echo esc_html( sprintf( __( 'Hi %s, we saved a special discount just for you.', 'flexi-revive-cart-pro' ), '{user_name}' ) ); ?></p>
          </td>
        </tr>

        <!-- Cart Items -->
        <tr>
          <td style="padding:24px 32px;">
            {cart_items}
            <p style="font-size:16px;font-weight:700;color:#1e1e1e;text-align:right;margin:8px 0 0;"><?php esc_html_e( 'Total:', 'flexi-revive-cart-pro' ); ?> {cart_total}</p>
          </td>
        </tr>

        <!-- Coupon code box -->
        <tr>
          <td style="padding:0 32px 16px;text-align:center;">
            <table role="presentation" align="center" cellpadding="0" cellspacing="0" style="border:2px dashed #46b450;border-radius:8px;padding:14px 32px;">
              <tr>
                <td>
                  <p style="margin:0;font-size:12px;color:#555;text-transform:uppercase;letter-spacing:1px;"><?php esc_html_e( 'Use Code:', 'flexi-revive-cart-pro' ); ?></p>
                  <p style="margin:4px 0 0;font-size:24px;font-weight:700;color:#1e1e1e;letter-spacing:2px;">{discount_code}</p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- CTA -->
        <tr>
          <td style="padding:8px 32px 32px;text-align:center;">
            <a href="{recovery_link}" style="display:inline-block;background:#46b450;color:#ffffff;text-decoration:none;padding:16px 40px;border-radius:6px;font-size:16px;font-weight:700;">
              <?php esc_html_e( 'Claim My Discount', 'flexi-revive-cart-pro' ); ?>
            </a>
            <p style="font-size:13px;color:#9ca3af;margin:16px 0 0;"><?php esc_html_e( 'Discount code expires in 72 hours.', 'flexi-revive-cart-pro' ); ?></p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f7f7f7;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;">
            <p style="font-size:12px;color:#9ca3af;margin:0;">
              &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> {store_name} &nbsp;|&nbsp;
              <a href="{unsubscribe_link}" style="color:#9ca3af;"><?php esc_html_e( 'Unsubscribe', 'flexi-revive-cart-pro' ); ?></a>
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
{tracking_pixel}
</body>
</html>
