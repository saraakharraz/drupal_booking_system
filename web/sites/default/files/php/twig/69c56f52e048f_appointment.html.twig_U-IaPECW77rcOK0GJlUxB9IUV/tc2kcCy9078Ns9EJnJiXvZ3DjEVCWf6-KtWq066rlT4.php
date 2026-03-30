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
        // line 1
        yield "<div class=\"appointment-view\">
  <div class=\"appointment-header\">
    <h2>";
        // line 3
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Appointment Details"));
        yield "</h2>
    <span class=\"appointment-status status--";
        // line 4
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["status"] ?? null), "html", null, true);
        yield "\">";
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, Twig\Extension\CoreExtension::capitalize($this->env->getCharset(), ($context["status"] ?? null)), "html", null, true);
        yield "</span>
  </div>

  <div class=\"appointment-fields\">
    <div class=\"field\">
      <label>";
        // line 9
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Customer Name"));
        yield "</label>
      <span>";
        // line 10
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["customer_name"] ?? null), "html", null, true);
        yield "</span>
    </div>

    <div class=\"field\">
      <label>";
        // line 14
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Email"));
        yield "</label>
      <span>";
        // line 15
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["customer_email"] ?? null), "html", null, true);
        yield "</span>
    </div>

    <div class=\"field\">
      <label>";
        // line 19
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Phone"));
        yield "</label>
      <span>";
        // line 20
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["customer_phone"] ?? null), "html", null, true);
        yield "</span>
    </div>

    <div class=\"field\">
      <label>";
        // line 24
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Appointment Date"));
        yield "</label>
      <span>";
        // line 25
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["appointment_date"] ?? null), "html", null, true);
        yield "</span>
    </div>

    <div class=\"field\">
      <label>";
        // line 29
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Agency"));
        yield "</label>
      <span>";
        // line 30
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["agency"] ?? null), "html", null, true);
        yield "</span>
    </div>

    <div class=\"field\">
      <label>";
        // line 34
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Adviser"));
        yield "</label>
      <span>";
        // line 35
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["adviser"] ?? null), "html", null, true);
        yield "</span>
    </div>

    <div class=\"field\">
      <label>";
        // line 39
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Appointment Type"));
        yield "</label>
      <span>";
        // line 40
        yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["appointment_type"] ?? null), "html", null, true);
        yield "</span>
    </div>

    ";
        // line 43
        if ((($tmp = ($context["notes"] ?? null)) && $tmp instanceof Markup ? (string) $tmp : $tmp)) {
            // line 44
            yield "      <div class=\"field\">
        <label>";
            // line 45
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->renderVar(t("Notes"));
            yield "</label>
        <span>";
            // line 46
            yield $this->extensions['Drupal\Core\Template\TwigExtension']->escapeFilter($this->env, ($context["notes"] ?? null), "html", null, true);
            yield "</span>
      </div>
    ";
        }
        // line 49
        yield "  </div>
</div>
";
        $this->env->getExtension('\Drupal\Core\Template\TwigExtension')
            ->checkDeprecations($context, ["status", "customer_name", "customer_email", "customer_phone", "appointment_date", "agency", "adviser", "appointment_type", "notes"]);        yield from [];
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
    public function isTraitable(): bool
    {
        return false;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getDebugInfo(): array
    {
        return array (  153 => 49,  147 => 46,  143 => 45,  140 => 44,  138 => 43,  132 => 40,  128 => 39,  121 => 35,  117 => 34,  110 => 30,  106 => 29,  99 => 25,  95 => 24,  88 => 20,  84 => 19,  77 => 15,  73 => 14,  66 => 10,  62 => 9,  52 => 4,  48 => 3,  44 => 1,);
    }

    public function getSourceContext(): Source
    {
        return new Source("", "modules/custom/appointment/templates/appointment.html.twig", "/var/www/html/web/modules/custom/appointment/templates/appointment.html.twig");
    }
    
    public function checkSecurity()
    {
        static $tags = ["if" => 43];
        static $filters = ["t" => 3, "escape" => 4, "capitalize" => 4];
        static $functions = [];

        try {
            $this->sandbox->checkSecurity(
                ['if'],
                ['t', 'escape', 'capitalize'],
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
