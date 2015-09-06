<?php

if (!defined('VENDOR')) {
    require_once('bootstrap.php');
}

require_once TEST . 'LucidFrameTestCase.php';

/**
 * Unit Test for session_helper.php
 */
class SecurityHelperTestCase extends LucidFrameTestCase
{
    public function testGET()
    {
        $input = 'Hello World';
        $this->assertEqual(_get($input), 'Hello World');

        $input = array(
            'str' => 'Hello World'
        );
        $this->assertEqual(_get($input), array(
            'str' => 'Hello World'
        ));

        $input = 'Hello <a href="javascript:alert(\'xss\');">World</a>';
        $this->assertEqual(_get($input), 'Hello World');

        $input = array(
            'str' => 'Hello World',
            'xss' => 'Hello <a href="javascript:alert(\'xss\');">World</a>'
        );
        $this->assertEqual(_get($input), array(
            'str' => 'Hello World',
            'xss' => 'Hello &lt;a href="javascript:alert(\'xss\');"&gt;World&lt;/a&gt;'
        ));

        $input = '<IMG SRC=javascript:alert("XSS")>';
        $this->assertEqual(_get($input), '');
    }

    public function testPOST()
    {
        $input = 'Hello World';
        $this->assertEqual(_post($input), 'Hello World');

        $input = array(
            'str' => 'Hello World'
        );
        $this->assertEqual(_post($input), array(
            'str' => 'Hello World'
        ));

        $input = 'Hello <a href="javascript:alert(\'xss\');">World</a>';
        $this->assertEqual(_post($input), 'Hello &lt;a href="javascript:alert(\'xss\');"&gt;World&lt;/a&gt;');

        $input = array(
            'str' => 'Hello World',
            'xss' => 'Hello <a href="javascript:alert(\'xss\');">World</a>'
        );
        $this->assertEqual(_post($input), array(
            'str' => 'Hello World',
            'xss' => 'Hello &lt;a href="javascript:alert(\'xss\');"&gt;World&lt;/a&gt;'
        ));

        $input = '<IMG SRC=javascript:alert("XSS")>';
        $this->assertEqual(_post($input), '&lt;IMG SRC=javascript:alert("XSS")&gt;');

        $input = '"Double quotes"';
        $this->assertEqual(_post($input), '"Double quotes"');

        $input = "'Single quotes'";
        $this->assertEqual(_post($input), "'Single quotes'");

        $input = "'Single quotes' & \"Double quotes\"";
        $this->assertEqual(_post($input), "'Single quotes' &amp; \"Double quotes\"");

        $input = "<b>Wörmann</b>";
        $this->assertEqual(_post($input), '&lt;b&gt;Wörmann&lt;/b&gt;');
    }

    public function testXSS()
    {
        $inputs = array(
            /* input => expected output */

            // unwanted tags
            '<object data="hack.swf" type="application/x-shockwave-flash"><param name="foo" value="bar"></object>' => '<param name="foo" value="bar">',
            '<OBJECT TYPE="text/x-scriptlet" DATA="http://ha.ckers.org/scriptlet.html"></OBJECT>' => '',
            'XSS attack <object data="hack.swf" type="application/x-shockwave-flash"></object>' => 'XSS attack ',
            'XSS attack <applet code="Bubbles.class">Java applet says XSS.</applet>' => 'XSS attack Java applet says XSS.',
            'XSS attack <embed src="hack.swf">' => 'XSS attack ',
            'XSS attack <iframe src="http://ha.ckers.org/scriptlet.html"></iframe>' => 'XSS attack ',
            'XSS attack <iframe src=http://ha.ckers.org/scriptlet.html></iframe>' => 'XSS attack ',
            '<form type="post">XSS attack</form>' => 'XSS attack',
            '<BASE HREF="javascript:alert(\'XSS\');//">' => '',
            '<EMBED SRC="http://ha.ckers.Using an EMBED tag you can embed a Flash movie that contains XSS. Click here for a demo. If you add the attributes allowScriptAccess="never" and allownetworking="internal" it can mitigate this risk (thank you to Jonathan Vanasco for the info).:
org/xss.swf" AllowScriptAccess="always"></EMBED>' => '',
            '<EMBED SRC="data:image/svg+xml;base64,PHN2ZyB4bWxuczpzdmc9Imh0dH A6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcv MjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hs aW5rIiB2ZXJzaW9uPSIxLjAiIHg9IjAiIHk9IjAiIHdpZHRoPSIxOTQiIGhlaWdodD0iMjAw IiBpZD0ieHNzIj48c2NyaXB0IHR5cGU9InRleHQvZWNtYXNjcmlwdCI+YWxlcnQoIlh TUyIpOzwvc2NyaXB0Pjwvc3ZnPg==" type="image/svg+xml" AllowScriptAccess="always"></EMBED>' => '',
            // TODO: 2.30 Double open angle brackets
            // '<iframe src=http://ha.ckers.org/scriptlet.html <' => '', // Double open angle brackets

            // <script> tag
            'XSS attack <SCRIPT>alert("XSS");</SCRIPT>' => 'XSS attack alert("XSS");',
            '<SCRIPT SRC=http://ha.ckers.org/xss.js></SCRIPT>' => '', // normal XSS JavaScript injection
            '<SCRIPT/XSS SRC="http://ha.ckers.org/xss.js"></SCRIPT>' => '', // Non-alpha-non-digit XSS
            '<SCRIPT/SRC="http://ha.ckers.org/xss.js"></SCRIPT>' => '', // Non-alpha-non-digit XSS
            '<SCRIPT/XSS SRC="http://ha.ckers.org/xss.js"></SCRIPT>' => '', // Non-alpha-non-digit XSS
            '<<SCRIPT>alert("XSS");//<</SCRIPT>' => '<alert("XSS");//<', // Extraneous open brackets
            'XSS<SCRIPT SRC=http://ha.ckers.org/xss.js?< B > attack' => 'XSS attack', // No closing script tags
            '<SCRIPT SRC=//ha.ckers.org/.j>XSS attack' => 'XSS attack', // Protocol resolution in script tags
            '</script><script>alert(\'XSS\');</script>' => 'alert(\'XSS\');', // Escaping JavaScript escapes
            '</TITLE><SCRIPT>alert("XSS");</SCRIPT>' => 'alert("XSS");', // End title tag
            '<SCRIPT SRC="http://ha.ckers.org/xss.jpg"></SCRIPT>' => '',

            // javascript:, vbscript:, livescript: protocols
            '<IMG """><SCRIPT>alert("XSS")</SCRIPT>">' => '<IMG """>alert("XSS")">', // Malformed IMG tags
            '<IMG SRC="javascript:alert(\'XSS\');">' => '<IMG SRC="noscript:alert(\'XSS\');">', // Image XSS using the JavaScript directive
            '<IMG SRC=javascript:alert(\'XSS\');>' => '<IMG SRC=noscript:alert(\'XSS\');>', // No quotes and no semicolon
            '<IMG SRC=JaVaScRiPt:alert(\'XSS\')>' => '<IMG SRC=noscript:alert(\'XSS\')>', // Case insensitive XSS attack vector
            '<IMG SRC=javascript:alert("XSS")>' => '<IMG SRC=noscript:alert("XSS")>', // The semicolons are required for this to work:
            '<IMG SRC="jav&#x0A;ascript:alert(\'XSS\');">' => '<IMG SRC="noscript:alert(\'XSS\');">', // Embedded newline to break up XSS
            '<IMG SRC="jav&#x0D;ascript:alert(\'XSS\');">' => '<IMG SRC="noscript:alert(\'XSS\');">', // Embedded carriage return to break up XSS
            '<IMG SRC="jav&#x09;ascript:alert(\'XSS\');">' => '<IMG SRC="noscript:alert(\'XSS\');">', // Embedded horizontal tab to break up XSS
            '<IMG SRC="jav	ascript:alert(\'XSS\');">' => '<IMG SRC="noscript:alert(\'XSS\');">', // Embedded horizontal tab to break up XSS
            '<IMG SRC=" &#14;  javascript:alert(\'XSS\');">' => '<IMG SRC="noscript:alert(\'XSS\');">', // Spaces and meta chars before the JavaScript
            '<IMG SRC=`javascript:alert("RSnake says, \'XSS\'")`>' => '<IMG SRC=`noscript:alert("RSnake says, \'XSS\'")`>', // Grave accent obfuscation
            'perl -e \'print "<IMG SRC=java\0script:alert(\"XSS\")>";\' > out' => 'perl -e \'print "<IMG SRC=noscript:alert("XSS")>";\' > out',
            '<IMG SRC=javascript:alert(String.fromCharCode(88,83,83))>' => '<IMG SRC=noscript:alert(String.fromCharCode(88,83,83))>', // fromCharCode
            'xss <IMG SRC="javascript:alert(\'XSS\')" attack' => 'xss <IMG SRC="noscript:alert(\'XSS\')" attack', // Half open HTML/JavaScript XSS vector
            '<INPUT TYPE="IMAGE" SRC="javascript:alert(\'XSS\');">' => '<INPUT TYPE="IMAGE" SRC="noscript:alert(\'XSS\');">', // Input image
            '<IMG DYNSRC="javascript:alert(\'XSS\')">' => '<IMG DYNSRC="noscript:alert(\'XSS\')">', // IMG Dynsrc
            '<IMG LOWSRC="javascript:alert(\'XSS\')">' => '<IMG LOWSRC="noscript:alert(\'XSS\')">', // IMG Lowsrc
            '<IMG SRC=\'vbscript:msgbox("XSS")\'>' => '<IMG SRC=\'noscript:msgbox("XSS")\'>', // VBscript in an image
            '<IMG SRC="livescript:[code]">' => '<IMG SRC="noscript:[code]">', // Livescript (older versions of Netscape only)
            '<STYLE>li {list-style-image: url("javascript:alert(\'XSS\')");}</STYLE><UL><LI>XSS</br>' => 'li {list-style-image: url("noscript:alert(\'XSS\')");}<UL><LI>XSS</br>', // List-style-image
            // -moz-binding CSS
            '<body style=\'-moz-binding:url("http://ha.ckers.org/xssmoz.xml#xss")\'></body>' => '<body style=\'noscript:url("http://ha.ckers.org/xssmoz.xml#xss")\'></body>',
            '<STYLE>BODY{-moz-binding:url("http://ha.ckers.org/xssmoz.xml#xss")}</STYLE>' => 'BODY{noscript:url("http://ha.ckers.org/xssmoz.xml#xss")}',
            '<STYLE type="text/css">BODY{-moz-binding:url("http://ha.ckers.org/xssmoz.xml#xss")}</STYLE>' => 'BODY{noscript:url("http://ha.ckers.org/xssmoz.xml#xss")}',
            // any attribute starting with "on" or xmlns
            '<IMG SRC=# onmouseover="alert(\'xxs\')">' => '<IMG SRC=# >',
            '<IMG SRC= onmouseover="alert(\'xxs\')">' => '<IMG SRC= >',
            '<IMG onmouseover="alert(\'xxs\')">' => '<IMG >',
            '<IMG SRC=/ onerror="alert(String.fromCharCode(88,83,83))"></img>' => '<IMG SRC=/ ></img>',
            '<BODY onload!#$%&()*~+-_.,:;?@[/|\]^`=alert("XSS")>' => '<BODY >',
            '<BODY ONLOAD=alert(\'XSS\')>' => '<BODY >',
            '<a onmouseover="alert(document.cookie)">xss link</a>' => '<a >xss link</a>',
            '<a onmouseover=alert(document.cookie)>xss link</a>' => '<a >xss link</a>',
            // dec/hex entities in tag
            'XSS <IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;> attack' => 'XSS <IMG SRC> attack',
            'XSS <IMG SRC=&#0000106&#0000097&#0000118&#0000097&#0000115&#0000099&#0000114&#0000105&#0000112&#0000116&#0000058&#0000097&#0000108&#0000101&#0000114&#0000116&#0000040&#0000039&#0000088&#0000083&#0000083&#0000039&#0000041> attack' => 'XSS <IMG SRC> attack',
            'XSS <IMG SRC=&#x6A&#x61&#x76&#x61&#x73&#x63&#x72&#x69&#x70&#x74&#x3A&#x61&#x6C&#x65&#x72&#x74&#x28&#x27&#x58&#x53&#x53&#x27&#x29> attack' => 'XSS <IMG SRC> attack',
            'XSS<BGSOUND SRC="javascript:alert(\'XSS\');"> attack' => 'XSS attack',
            // TODO: 2.43 BR & JavaScript includes
            // '<BR SIZE="&{alert(\'XSS\')}">' => '',

            '<LINK REL="stylesheet" HREF="javascript:alert(\'XSS\');">' => '',
            '<LINK REL="stylesheet" HREF="http://ha.ckers.org/xss.css">' => '',
            '<STYLE>@import\'http://ha.ckers.org/xss.css\';</STYLE>' => '@import\'http://ha.ckers.org/xss.css\';',
            '<STYLE>@im\port\'\ja\vasc\ript:alert("XSS")\';</STYLE>' => '@import\'noscript:alert("XSS")\';', // STYLE tags with broken up JavaScript for XSS
            '<img src="" style="margin:3px" vspace="1" hspace="1" />' => '<img src="" style="margin:3px" vspace="1" hspace="1" />', // OK
            '<STYLE TYPE="text/javascript">alert(\'XSS\');</STYLE>' => 'alert(\'XSS\');', // STYLE tag (Older versions of Netscape only)
            '<STYLE>.XSS{background-image:url("javascript:alert(\'XSS\')");}</STYLE><A CLASS=XSS></A>' => '.XSS{background-image:url("noscript:alert(\'XSS\')");}<A CLASS=XSS></A>', // STYLE tag using background-image
            '<STYLE type="text/css">BODY{background:url("javascript:alert(\'XSS\')")}</STYLE>' => 'BODY{background:url("noscript:alert(\'XSS\')")}',
            '<IMG STYLE="xss:expr/*XSS*/ession(alert(\'XSS\'))">' => '<IMG >', // STYLE attribute using a comment to break up expression
            '<XSS STYLE="xss:expression(alert(\'XSS\'))">' => '<XSS >', // Anonymous HTML with STYLE attribute
            '<XSS STYLE="behavior: url(xss.htc);">' => '<XSS >', // Local htc file
            '<DIV STYLE="width: expression(alert(\'XSS\'));">' => '<DIV >', // DIV expression
            // TODO: 2.57 US-ASCII encoding
            // '¼script¾alert(¢XSS¢)¼/script¾' => '¼script¾alert(¢XSS¢)¼/script¾',// US-ASCII encoding

            '<META HTTP-EQUIV="Link" Content="<http://ha.ckers.org/xss.css>; REL=stylesheet">' => '; REL=stylesheet">',
            '<META HTTP-EQUIV="refresh" CONTENT="0;url=javascript:alert(\'XSS);">' => '',
            '<META HTTP-EQUIV="refresh" CONTENT="0;url=javascript:alert(\'XSS\');">' => '',
            '<META HTTP-EQUIV="refresh" CONTENT="0;url=data:text/html base64,PHNjcmlwdD5hbGVydCgnWFNTJyk8L3NjcmlwdD4K">' => '', // META using data
            '<META HTTP-EQUIV="refresh" CONTENT="0; URL=http://;URL=javascript:alert(\'XSS\');">' => '', // META with additional URL parameter
            '<META HTTP-EQUIV="Set-Cookie" Content="USERID=<SCRIPT>alert(\'XSS\')</SCRIPT>">' => 'alert(\'XSS\')">', // Cookie manipulation

            '<IFRAME SRC="javascript:alert(\'XSS\');"></IFRAME>xss attack' => 'xss attack', // IFRAME
            '<IFRAME SRC=# onmouseover="alert(document.cookie)"></IFRAME>' => '', // IFRAME Event based
            '<FRAMESET><FRAME SRC="javascript:alert(\'XSS\');"></FRAMESET>' => '', // FRAME
            '<TABLE BACKGROUND="javascript:alert(\'XSS\')">' => '<TABLE BACKGROUND="noscript:alert(\'XSS\')">', // TABLE
            '<DIV STYLE="background-image: url(javascript:alert(\'XSS\'))">' => '<DIV STYLE="background-image: url(noscript:alert(\'XSS\'))">',
            // TODO: 2.63.2 DIV background-image with unicoded XSS exploit
            // '<DIV STYLE="background-image:\0075\0072\006C\0028\'\006a\0061\0076\0061\0073\0063\0072\0069\0070\0074\003a\0061\006c\0065\0072\0074\0028.1027\0058.1053\0053\0027\0029\'\0029">' => '<DIV STYLE="background-image:07507206C028\'06a06107606107306307206907007403a06106c065072074028.1027058.1053053027029\'029">',

            '<!--[if gte IE 4]> <SCRIPT>alert(\'XSS\');</SCRIPT> <![endif]-->' => '<!--[if gte IE 4]> alert(\'XSS\'); <![endif]-->', // Downlevel-Hidden block
            // Locally hosted XML with embedded JavaScript that is generated using an XML data island
            // The three of the following works only in IE and Netscape 8.1
            '<XML SRC="xsstest.xml" ID=I></XML><SPAN DATASRC=#I DATAFLD=C DATAFORMATAS=HTML></SPAN>' => '<SPAN DATASRC=#I DATAFLD=C DATAFORMATAS=HTML></SPAN>',
            '<XML ID="xss"><I><B><IMG SRC="javas<!-- -->cript:alert(\'XSS\')"></B></I></XML><SPAN DATASRC="#xss" DATAFLD="B" DATAFORMATAS="HTML"></SPAN>' => '<I><B><IMG SRC="noscript:alert(\'XSS\')"></B></I><SPAN DATASRC="#xss" DATAFLD="B" DATAFORMATAS="HTML"></SPAN>',
            '<HTML><BODY><?xml:namespace prefix="t" ns="urn:schemas-microsoft-com:time"><?import namespace="t" implementation="#default#time2"><t:set attributeName="innerHTML" to="XSS<SCRIPT DEFER>alert("XSS")</SCRIPT>"></BODY></HTML>' => '<HTML><BODY><?import namespace="t" implementation="#default#time2"><t:set attributeName="innerHTML" to="XSSalert("XSS")"></BODY></HTML>', // HTML+TIME in XML
            // SSI (Server Side Includes)
            '<!--#exec cmd="/bin/echo \'<SCR\'"--><!--#exec cmd="/bin/echo \'IPT SRC=http://ha.ckers.org/xss.js></SCRIPT>\'"-->' => '\'"-->',
            // TODO: 2.75 PHP tag
            /* '<? echo(\'<SCR)\'; echo(\'IPT>alert("XSS")</SCRIPT>\'); ?>' => '', */
            '<IMG SRC="http://www.thesiteyouareon.com/somecommand.php?somevariables=maliciouscode">' => '<IMG SRC="http://www.thesiteyouareon.com/somecommand.php?somevariables=maliciouscode">', // IMG Embedded commands
            '<HEAD><META HTTP-EQUIV="CONTENT-TYPE" CONTENT="text/html; charset=UTF-7"> </HEAD>+ADw-SCRIPT+AD4-alert(\'XSS\');+ADw-/SCRIPT+AD4-' => '<HEAD> </HEAD>+ADw-SCRIPT+AD4-alert(\'XSS\');+ADw-/SCRIPT+AD4-', // UTF-7 encoding

            // XSS using HTML quote encapsulation
            '<SCRIPT a=">" SRC="http://ha.ckers.org/xss.js"></SCRIPT>' => '" SRC="http://ha.ckers.org/xss.js">',
            '<SCRIPT =">" SRC="http://ha.ckers.org/xss.js"></SCRIPT>' => '" SRC="http://ha.ckers.org/xss.js">',
            '<SCRIPT a=">" \'\' SRC="http://ha.ckers.org/xss.js"></SCRIPT>' => '" \'\' SRC="http://ha.ckers.org/xss.js">',
            '<SCRIPT "a=\'>\'" SRC="http://ha.ckers.org/xss.js"></SCRIPT>' => '\'" SRC="http://ha.ckers.org/xss.js">',
            '<SCRIPT a=`>` SRC="http://ha.ckers.org/xss.js"></SCRIPT>' => '` SRC="http://ha.ckers.org/xss.js">',
            '<SCRIPT a=">\'>" SRC="http://ha.ckers.org/xss.js"></SCRIPT>' => '\'>" SRC="http://ha.ckers.org/xss.js">',
            '<SCRIPT>document.write("<SCRI");</SCRIPT>PT SRC="http://ha.ckers.org/xss.js"></SCRIPT>' => 'document.write("<SCRI");PT SRC="http://ha.ckers.org/xss.js">',

            // TODO: 2.80 URL string evasion
        );

        foreach ($inputs as $input => $expected) {
            $this->assertEqual(_xss($input), $expected);
        }
    }
}
