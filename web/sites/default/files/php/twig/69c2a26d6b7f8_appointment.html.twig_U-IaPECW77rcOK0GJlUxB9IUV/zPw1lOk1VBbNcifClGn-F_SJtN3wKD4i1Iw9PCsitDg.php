<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;
use Twig\TemplateWrapper;

/* modules/custom/appointment/templates/appointment.html.twig */
class __TwigTemplate_8007ba994a3f68d1ddff67b7701ae67f extends Template
{
    private Source $source;
    /**
     * @var array<string, Template>
     */
    private array $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
        $this->sandbox = $this->extensions[SandboxExtension::class];
        $this->checkSecurity();
    }

    protected function doDisplay(array $context, array $blocks = []): iterable
    {
        $macros = $this->macros;
        // line 2
        yield "
";
        // line 10
        yield "
";
        // line 18
        yield "
";
        // line 27
        yield "
";
        // line 29
        yield "
";
        // line 34
        yield "
";
        // line 41
        yield "
";
        // line 54
        yield "
";
        // line 67
        yield "
";
        // line 80
        yield "
";
        // line 85
        yield "
";
        // line 92
        yield "
";
        // line 97
        yield "
";
        // line 104
        yield "
";
        // line 106
        yield "
";
        yield from [];
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTemplateName(): string
    {
        return "modules/custom/appointment/templates/appointment.html.twig";
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  86 => 106,  83 => 104,  80 => 97,  77 => 92,  74 => 85,  71 => 80,  68 => 67,  65 => 54,  62 => 41,  59 => 34,  56 => 29,  53 => 27,  50 => 18,  47 => 10,  44 => 2,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "modules/custom/appointment/templates/appointment.html.twig", "/var/www/html/web/modules/custom/appointment/templates/appointment.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = [];
        static $filters = [];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                [],
                [],
                [],
                $this->source
            );
        } catch (SecurityError $e) {
            $e->setSourceContext($this->source);

            if ($e instanceof SecurityNotAllowedTagError && isset($tags[$e->getTagName()])) {
                $e->setTemplateLine($tags[$e->getTagName()]);
            } elseif ($e instanceof SecurityNotAllowedFilterError && isset($filters[$e->getFilterName()])) {
                $e->setTemplateLine($filters[$e->getFilterName()]);
            } elseif ($e instanceof SecurityNotAllowedFunctionError && isset($functions[$e->getFunctionName()])) {
                $e->setTemplateLine($functions[$e->getFunctionName()]);
            }

            throw $e;
        }

    }
}
