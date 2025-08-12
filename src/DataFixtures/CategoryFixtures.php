<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            [
                'name' => 'Personal & Household Expenses',
                'description' => 'Charities and Associations, Child Care, Cleaning, Laundry and Alterations, Personal Services, Pet Care, Security, Telecommunications and Utilities',
                'icon' => 'fas fa-home',
            ],
            [
                'name' => 'Professional and Financial Services',
                'description' => 'Business Services, Equipment, Financial services, Government, Legal Costs, Professional services',
                'icon' => 'fas fa-briefcase',
            ],
            [
                'name' => 'Retail and Grocery',
                'description' => 'Alcohol and Tobacco, Arts & Crafts, Hobbies, Clothing and Shoes, Department and Discount Stores, Food and Grocery, Specialty Retail',
                'icon' => 'fas fa-shopping-cart',
            ],
            [
                'name' => 'Transportation',
                'description' => 'Airlines, Automotive, Car Rental Services, Gas/Fuel, Other Transportation',
                'icon' => 'fas fa-car',
            ],
            [
                'name' => 'Hotels, Entertainment, and Recreation',
                'description' => 'Entertainment, Hotels, Accommodation and Cruise ships, Recreational',
                'icon' => 'fas fa-bed',
            ],
            [
                'name' => 'Restaurants',
                'description' => 'Restaurants',
                'icon' => 'fas fa-utensils',
            ],
            [
                'name' => 'Home & Office Improvement',
                'description' => 'Furniture, Appliances and Electronics, Home Improvement and Hardware, Renovations and Landscaping, Repairs',
                'icon' => 'fas fa-tools',
            ],
            [
                'name' => 'Health & Education',
                'description' => 'Education, Health Care Services',
                'icon' => 'fas fa-graduation-cap',
            ],
            [
                'name' => 'Cash Advances, Balance Transfers',
                'description' => 'Cash Advances, Balance Transfers, CIBC GMT',
                'icon' => 'fas fa-exchange-alt',
            ],
            [
                'name' => 'Foreign Currency Transactions',
                'description' => 'Foreign Currency Transactions, Transfers to WISE, Transfers to foreign cards',
                'icon' => 'fas fa-globe',
            ],
            [
                'name' => 'Other Transactions',
                'description' => 'Any Other Transactions which not related to any of the categories',
                'icon' => 'fas fa-question',
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = new Category();
            $category->setName($categoryData['name']);
            $category->setDescription($categoryData['description']);
            $category->setIcon($categoryData['icon']);

            $manager->persist($category);
        }

        $manager->flush();
    }
}
