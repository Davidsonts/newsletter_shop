<div class="ses4_wrap">
<div class="caixa_texto">
<h1>Que tal ficar por dentro de novidades em primeira mão?</h1>
<p>Se inscreva em nossa Newsletter e descubra promoções imperdíveis, além de dicas e workshops para se tornar um verdadeiro Eletricista de Elite</p>
</div>
    <div class="caixa_info">
        {* <form action="{$urls.pages.contact}" method="post"> *}
        {* <form class="form-horizontal" enctype="multipart/form-data" action="http://fingerdesenvolvimento.com.br/area/module/newsletter_shop/submit" method="POST"> *}
        <form action="{$link->getModuleLink('newsletter_shop', 'newslettershop')|escape:'html'}" method="post">

            <input type="text" placeholder="Nome" name="name" class="info" /> 
            <input type="email" placeholder="Email" name="email" class="info" /> 
            <input type="hidden" name="token" value="{$token}" />
            
            <button type="submit" name="sendNewsletter"> Enviar </button>
        </form>
    </div>
</div>