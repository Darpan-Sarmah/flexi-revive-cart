<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset( $email_subject ) ? esc_html( $email_subject ) : esc_html__( 'Your cart is waiting – act fast!', 'flexi-revive-cart' ); ?></title>
</head>
<body style="margin:0;padding:0;background:#f7f7f7;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f7f7;padding:20px 0;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;max-width:600px;width:100%;">

        <!-- Header -->
        <tr>
          <td style="background:#d63638;padding:28px 32px;text-align:center;">
            <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:700;">{store_name}</h1>
          </td>
        </tr>

        <!-- Urgency banner -->
        <tr>
          <td style="background:#fff3cd;padding:14px 32px;text-align:center;border-bottom:2px solid #ffc107;">
            <p style="margin:0;font-size:14px;font-weight:700;color:#856404;">⚠️ <?php esc_html_e( 'Items in your cart are selling fast – don\'t miss out!', 'flexi-revive-cart' ); ?></p>
          </td>
        </tr>

        <!-- Hero -->
        <tr>
          <td style="padding:36px 32px 0;text-align:center;">
            <h2 style="font-size:22px;color:#1e1e1e;margin:0 0 8px;"><?php esc_html_e( 'Your cart is still waiting…', 'flexi-revive-cart' ); ?></h2>
            <p style="font-size:15px;color:#555;margin:0;"><?php echo esc_html( sprintf( __( 'Hi %s,', 'flexi-revive-cart' ), '{user_name}' ) ); ?></p>
            <p style="font-size:15px;color:#555;margin:12px 0 0;"><?php esc_html_e( 'Your cart was saved {abandoned_time}. These items are popular and inventory is limited.', 'flexi-revive-cart' ); ?></p>
          </td>
        </tr>

        <!-- Cart Items -->
        <tr>
          <td style="padding:24px 32px;">
            {cart_items}
            <p style="font-size:16px;font-weight:700;color:#1e1e1e;text-align:right;margin:8px 0 0;"><?php esc_html_e( 'Total:', 'flexi-revive-cart' ); ?> {cart_total}</p>
          </td>
        </tr>

        <!-- CTA -->
        <tr>
          <td style="padding:8px 32px 32px;text-align:center;">
            <a href="{recovery_link}" style="display:inline-block;background:#d63638;color:#ffffff;text-decoration:none;padding:16px 40px;border-radius:6px;font-size:16px;font-weight:700;">
              <?php esc_html_e( 'Secure My Cart Now', 'flexi-revive-cart' ); ?>
            </a>
            <p style="font-size:13px;color:#9ca3af;margin:16px 0 0;"><?php esc_html_e( 'Free standard shipping on orders over $50.', 'flexi-revive-cart' ); ?></p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f7f7f7;border-top:1px solid #e5e7eb;padding:20px 32px;text-align:center;">
            <p style="font-size:12px;color:#9ca3af;margin:0;">
              &copy; <?php echo esc_html( date( 'Y' ) ); // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date ?> {store_name} &nbsp;|&nbsp;
              <a href="{unsubscribe_link}" style="color:#9ca3af;"><?php esc_html_e( 'Unsubscribe', 'flexi-revive-cart' ); ?></a>
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
