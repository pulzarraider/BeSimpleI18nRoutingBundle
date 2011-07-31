<?php

namespace BeSimple\I18nRoutingBundle\Routing;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use BeSimple\I18nRoutingBundle\Routing\Translator\AttributeTranslatorInterface;
use Symfony\Component\HttpFoundation\Session;
use Symfony\Component\Routing\RequestContext;

class Router implements RouterInterface
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var AttributeTranslatorInterface
     */
    private $translator;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * Constructor
     *
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param null|\Symfony\Component\HttpFoundation\Session $session
     * @param Translator\AttributeTranslatorInterface|null $translator
     */
    public function __construct(RouterInterface $router, Session $session = null, AttributeTranslatorInterface $translator = null)
    {
        $this->router = $router;
        $this->session    = $session;
        $this->translator = $translator;
    }

    /**
     * Generates a URL from the given parameters.
     *
     * @param  string  $name       The name of the route
     * @param  array   $parameters An array of parameters
     * @param  Boolean $absolute   Whether to generate an absolute URL
     *
     * @return string The generated URL
     *
     * @throws \InvalidArgumentException When the route doesn't exists
     */
    public function generate($name, $parameters = array(), $absolute = false)
    {
        if (isset($parameters['locale']) || isset($parameters['translate'])) {
            $locale = isset($parameters['locale']) ? $parameters['locale'] : $this->session->getLocale();
            unset($parameters['locale']);

            if (isset($parameters['translate'])) {
                foreach (array($parameters['translate']) as $translateAttribute) {
                    $parameters[$translateAttribute] = $this->translator->reverseTranslate(
                        $name, $locale, $translateAttribute, $parameters[$translateAttribute]
                    );
                }
                unset($parameters['translate']);
            }

            return $this->generateI18n($name, $locale, $parameters, $absolute);
        }

        try {
            return $this->router->generate($name, $parameters, $absolute);
        } catch (RouteNotFoundException $e) {
            if (null !== $this->session) {
                // at this point here we would never have $parameters['translate'] due to condition before
                return $this->generateI18n($name, $this->session->getLocale(), $parameters, $absolute);
            } else {
                throw $e;
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function match($url)
    {
        $match = $this->router->match($url);

        // if a _locale parameter isset remove the .locale suffix that is appended to each route in I18nRoute
        if (!empty($match['_locale']) && preg_match('#^(.+)\.'.preg_quote($match['_locale'], '#').'+$#', $match['_route'], $route)) {
            $match['_route'] = $route[1];

            // now also check if we want to translate parameters:
            if (isset($match['_translate'])) {
                foreach ((array)$match['_translate'] as $attribute) {
                    $match[$attribute] = $this->translator->translate(
                        $match['_route'], $match['_locale'], $attribute, $match[$attribute]
                    );
                }
            }
        }

        return $match;
    }

    public function setContext(RequestContext $context)
    {
        $this->router->setContext($context);
    }

    /**
     * Generates a I18N URL from the given parameter
     *
     * @param string   $name       The name of the I18N route
     * @param string   $locale     The locale of the I18N route
     * @param  array   $parameters An array of parameters
     * @param  Boolean $absolute   Whether to generate an absolute URL
     *
     * @return string The generated URL
     *
     * @throws RouteNotFoundException When the route doesn't exists
     */
    private function generateI18n($name, $locale, $parameters, $absolute)
    {
        try {
            return $this->router->generate($name.'.'.$locale, $parameters, $absolute);
        } catch (RouteNotFoundException $e) {
            throw new RouteNotFoundException(sprintf('I18nRoute "%s" (%s) does not exist.', $name, $locale));
        }
    }
}
