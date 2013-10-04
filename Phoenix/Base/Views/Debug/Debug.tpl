<div id="phoenix_debug">
    <table>
        <tr>
            <th>Query</th>
            <th>Params</th>
        </tr>
        {foreach from=$queries item=qu}
        <tr>
            <td>{$qu.query}</td>
            <td>
                {foreach from=$qu.params key=k item=v}
                    {$k} = {$v}<br>
                {/foreach}
            </td>
        </tr>
        {/foreach}
    </table>
</div>