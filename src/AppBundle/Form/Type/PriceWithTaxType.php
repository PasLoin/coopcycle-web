<?php

namespace AppBundle\Form\Type;

use AppBundle\Form\Type\MoneyType;
use Sylius\Component\Product\Resolver\ProductVariantResolverInterface;
use Sylius\Component\Taxation\Calculator\CalculatorInterface;
use Sylius\Component\Taxation\Resolver\TaxRateResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceWithTaxType extends AbstractType
{
    private $variantResolver;
    private $taxRateResolver;
    private $calculator;

    public function __construct(
        ProductVariantResolverInterface $variantResolver,
        TaxRateResolverInterface $taxRateResolver,
        CalculatorInterface $calculator)
    {
        $this->variantResolver = $variantResolver;
        $this->taxRateResolver = $taxRateResolver;
        $this->calculator = $calculator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('taxExcluded', MoneyType::class, [
                'mapped' => false,
                'label' => 'form.price_with_tax.tax_excl.label'
            ])
            ->add('taxIncluded', MoneyType::class, [
                'mapped' => false,
                'label' => 'form.price_with_tax.tax_incl.label'
            ])
            ->add('taxCategory', ProductTaxCategoryChoiceType::class, [
                'mapped' => false,
                'label' => 'form.product.taxCategory.label',
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {

            $form = $event->getForm();
            $product = $form->getParent()->getData();

            if (null !== $product->getId()) {

                $variant = $this->variantResolver->getVariant($product);
                $taxRate = $this->taxRateResolver->resolve($variant);

                $taxAmount = (int) $this->calculator->calculate($variant->getPrice(), $taxRate);

                $form->get('taxExcluded')->setData($variant->getPrice() - $taxAmount);
                $form->get('taxIncluded')->setData($variant->getPrice());
                $form->get('taxCategory')->setData($variant->getTaxCategory());
            }
        });
    }

    public function getBlockPrefix()
    {
        return 'coopcycle_price_with_tax';
    }
}
