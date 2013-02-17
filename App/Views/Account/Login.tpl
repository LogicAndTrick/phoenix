{nocache}
{if Authentication::$useopenid}
    {form}
        <h2>Login With OpenID</h2>
        <p>
            {label for=Authentication::$post_openid text='OpenID'}
            {field type=openid for=Authentication::$post_openid}
            {validation for=Authentication::$post_openid}
        </p>
        <p>
            {field for=Authentication::$post_remember type=checkbox}
            {label for=Authentication::$post_remember text='Remember Me'}
        </p>
        <p>
            {submit}
        </p>
    {/form}
{/if}
{form}
    <h2>Login With Account</h2>
    <p>
        {label for=Authentication::$post_username text=Username}
        {field for=Authentication::$post_username}
        {validation for=Authentication::$post_username}
    </p>
    <p>
        {label for=Authentication::$post_password text=Password}
        {field type=password for=Authentication::$post_password}
        {validation for=Authentication::$post_password}
    </p>
    <p>
        {label for=Authentication::$post_remember text='Remember me'}
        {field for=Authentication::$post_remember type=checkbox}
    </p>
    <p>
        {submit}
    </p>
{/form}
{/nocache}