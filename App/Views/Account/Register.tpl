{nocache}
{validationsummary}
{if Authentication::$useopenid}
    {form}
        <h2>Register an OpenID</h2>
        <p>
            {hidden for=Authentication::$post_register value=openid}
            {label for=Authentication::$post_username text=Username}
            {field for=Authentication::$post_username}
            {validation for=Authentication::$post_username}
        </p>

        <p>
            {label for=Authentication::$post_email text=Email}
            {field for=Authentication::$post_email}
            {validation for=Authentication::$post_email}
        </p>

        <p>
            {label for=Authentication::$post_openid text='OpenID'}
            {field for=Authentication::$post_openid type=openid}
            {validation for=Authentication::$post_openid}
        </p>

        <p>
            {submit value='Register OpenID'}
        </p>
    {/form}
{/if}
{form}
    <h2>Register an Account</h2>
    <p>
        {hidden for=Authentication::$post_register value=account}
        {label html_for="{Authentication::$post_username}_Account" text=Username}
        {field for=Authentication::$post_username html_id="{Authentication::$post_username}_Account"}
        {validation for=Authentication::$post_username}
    </p>

    <p>
        {label html_for="{Authentication::$post_email}_Account" text=Email}
        {field for=Authentication::$post_email html_id="{Authentication::$post_email}_Account"}
        {validation for=Authentication::$post_email}
    </p>

    <p>
        {label for=Authentication::$post_password text=Password}
        {field type=password for=Authentication::$post_password}
        {validation for=Authentication::$post_password}
    </p>

    <p>
        {label for=Authentication::$post_password_confirm text='Confirm Password'}
        {field type=password for=Authentication::$post_password_confirm}
        {validation for=Authentication::$post_password_confirm}
    </p>

    <p>
        {submit value='Register Account'}
    </p>
{/form}
{/nocache}