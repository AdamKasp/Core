<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Sylius\Component\Core\Factory;

use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Factory\PromotionRuleFactory;
use Sylius\Component\Core\Factory\PromotionRuleFactoryInterface;
use Sylius\Component\Core\Promotion\Checker\Rule\ContainsTaxonRuleChecker;
use Sylius\Component\Core\Promotion\Checker\Rule\NthOrderRuleChecker;
use Sylius\Component\Core\Promotion\Checker\Rule\TaxonRuleChecker;
use Sylius\Component\Core\Promotion\Checker\Rule\TotalOfItemsFromTaxonRuleChecker;
use Sylius\Component\Promotion\Checker\Rule\CartQuantityRuleChecker;
use Sylius\Component\Promotion\Checker\Rule\ItemTotalRuleChecker;
use Sylius\Component\Promotion\Model\PromotionRuleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

/**
 * @mixin PromotionRuleFactory
 *
 * @author Mateusz Zalewski <mateusz.zalewski@lakion.com>
 */
final class PromotionRuleFactorySpec extends ObjectBehavior
{
    function let(FactoryInterface $decoratedFactory)
    {
        $this->beConstructedWith($decoratedFactory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(PromotionRuleFactory::class);
    }

    function it_implements_a_rule_factory_interface()
    {
        $this->shouldImplement(PromotionRuleFactoryInterface::class);
    }

    function it_uses_a_decorated_factory_to_create_new_rule_object(
        FactoryInterface $decoratedFactory,
        PromotionRuleInterface $rule
    ) {
        $decoratedFactory->createNew()->willReturn($rule);

        $this->createNew()->shouldReturn($rule);
    }

    function it_creates_a_cart_quantity_rule(FactoryInterface $decoratedFactory, PromotionRuleInterface $rule)
    {
        $decoratedFactory->createNew()->willReturn($rule);
        $rule->setType(CartQuantityRuleChecker::TYPE)->shouldBeCalled();
        $rule->setConfiguration(['count' => 5])->shouldBeCalled();

        $this->createCartQuantity(5)->shouldReturn($rule);
    }

    function it_creates_an_item_total_rule(FactoryInterface $decoratedFactory, PromotionRuleInterface $rule)
    {
        $decoratedFactory->createNew()->willReturn($rule);
        $rule->setType(ItemTotalRuleChecker::TYPE)->shouldBeCalled();
        $rule->setConfiguration(['amount' => 1000])->shouldBeCalled();

        $this->createItemTotal(1000)->shouldReturn($rule);
    }

    function it_creates_a_taxon_rule(FactoryInterface $decoratedFactory, PromotionRuleInterface $rule)
    {
        $decoratedFactory->createNew()->willReturn($rule);
        $rule->setType(TaxonRuleChecker::TYPE)->shouldBeCalled();
        $rule->setConfiguration(['taxons' => [1, 6]])->shouldBeCalled();

        $this->createTaxon([1, 6])->shouldReturn($rule);
    }

    function it_creates_a_total_of_items_from_taxon_rule(
        FactoryInterface $decoratedFactory,
        PromotionRuleInterface $rule
    ) {
        $decoratedFactory->createNew()->willReturn($rule);
        $rule->setType(TotalOfItemsFromTaxonRuleChecker::TYPE)->shouldBeCalled();
        $rule->setConfiguration(['taxon' => 'spears', 'amount' => 1000])->shouldBeCalled();

        $this->createItemsFromTaxonTotal('spears', 1000)->shouldReturn($rule);
    }

    function it_creates_a_contains_taxon_rule(FactoryInterface $decoratedFactory, PromotionRuleInterface $rule)
    {
        $decoratedFactory->createNew()->willReturn($rule);
        $rule->setType(ContainsTaxonRuleChecker::TYPE)->shouldBeCalled();
        $rule->setConfiguration(['taxon' => 'bows', 'count' => 10])->shouldBeCalled();

        $this->createContainsTaxon('bows', 10)->shouldReturn($rule);
    }

    function it_creates_a_nth_order_rule(FactoryInterface $decoratedFactory, PromotionRuleInterface $rule)
    {
        $decoratedFactory->createNew()->willReturn($rule);
        $rule->setType(NthOrderRuleChecker::TYPE)->shouldBeCalled();
        $rule->setConfiguration(['nth' => 10])->shouldBeCalled();

        $this->createNthOrder(10)->shouldReturn($rule);
    }
}
