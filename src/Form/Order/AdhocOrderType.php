<?php

namespace AppBundle\Form\Order;

use AppBundle\Form\AddressType;
use AppBundle\Form\StripePaymentType;
use AppBundle\Sylius\Order\OrderInterface;
use AppBundle\Form\Type\AsapChoiceLoader;
use AppBundle\Form\Type\TsRangeChoice;
use AppBundle\Service\TimeRegistry;
use AppBundle\Translation\DatePeriodFormatter;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdhocOrderType extends AbstractType
{
    private $timeRegistry;
    private $orderProcessor;
    private $datePeriodFormatter;

    public function __construct(
        TimeRegistry $timeRegistry,
        OrderProcessorInterface $orderProcessor,
        DatePeriodFormatter $datePeriodFormatter)
    {
        $this->timeRegistry = $timeRegistry;
        $this->orderProcessor = $orderProcessor;
        $this->datePeriodFormatter = $datePeriodFormatter;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('shippingAddress', AddressType::class, [
                'with_widget' => true,
                'with_description' => false,
            ])
            ;

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) use ($options) {

            $form = $event->getForm();
            $cart = $event->getData();

            $vendor = $cart->getVendor();
            $fulfillmentMethod = $cart->getFulfillmentMethodObject();

            $choiceLoader = new AsapChoiceLoader(
                $fulfillmentMethod->getOpeningHours(),
                $this->timeRegistry,
                $vendor->getClosingRules(),
                $fulfillmentMethod->getOrderingDelayMinutes(),
                $fulfillmentMethod->getOption('range_duration', 10),
                $fulfillmentMethod->isPreOrderingAllowed()
            );

            $payment = $cart->getLastPayment();
            $pendingPayment = in_array($payment->getState(), [PaymentInterface::STATE_CART, PaymentInterface::STATE_NEW]);

            $form->add('shippingTimeRange', ChoiceType::class, [
                'label' => 'form.delivery.time_slot.label',
                'choice_loader' => $choiceLoader,
                'choice_label' => function(TsRangeChoice $choice) {
                    return $this->datePeriodFormatter->toHumanReadable($choice->toDatePeriod());
                },
                'choice_value' => function ($choice) {
                    return $choice;
                },
                'data' => ($options['with_payment'] || !$pendingPayment) ?
                    new TsRangeChoice($cart->getShippingTimeRange()) : null,
                'mapped' => false,
                'disabled' => $options['with_payment'] || !$pendingPayment,
            ]);

            $form->add('shippingAddress', AddressType::class, [
                'with_widget' => true,
                'with_description' => false,
                'label' => 'Dirección',
                'disabled' => $options['with_payment'] || !$pendingPayment,
            ]);

            if ($options['with_payment'] && $pendingPayment) {
                $form->add('payment', StripePaymentType::class, [
                    'data' => $payment,
                    'mapped' => false,
                ]);
            }

        });

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {

                $form = $event->getForm();
                $order = $form->getData();

                $order->setShippingTimeRange($form->get('shippingTimeRange')->getData()->toTsRange());

                $this->orderProcessor->process($order);
            }
        );

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => OrderInterface::class,
                'with_payment' => false,
            ]);
    }
}
